<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\MovimientoControlado;
use App\Services\LibroControladosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

class ControladosTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    private function loteConIngreso(int $cantidad = 20): array
    {
        $qf = $this->usuario('quimico');
        $p = $this->producto(controlado: true);
        $lote = $this->lote($p, 'MF-2209', 300, $cantidad);
        app(LibroControladosService::class)->registrar($lote, 'ingreso', $cantidad, $qf, $qf, 'Saldo inicial');

        return [$qf, $p, $lote];
    }

    public function test_quimico_firma_egreso_con_su_contrasena(): void
    {
        [$qf, , $lote] = $this->loteConIngreso();

        $this->actingAs($qf)->post(route('controlados.egreso'), [
            'lote_id' => $lote->id, 'cantidad' => 2, 'observacion' => 'Uso en box', 'password' => 'Clave#Segura2026',
        ])->assertSessionHas('ok');

        $this->assertSame(18, $lote->fresh()->cantidad_actual);
        $ultimo = MovimientoControlado::orderByDesc('id')->first();
        $this->assertSame(18, $ultimo->saldo_resultante);
        $this->assertSame(MovimientoControlado::orderBy('id')->first()->hash, $ultimo->hash_anterior);
    }

    public function test_firma_con_contrasena_incorrecta_no_registra_nada(): void
    {
        [$qf, , $lote] = $this->loteConIngreso();

        $this->actingAs($qf)->post(route('controlados.egreso'), [
            'lote_id' => $lote->id, 'cantidad' => 2, 'observacion' => 'Uso en box', 'password' => 'otra-clave',
        ])->assertSessionHasErrors('password');

        $this->assertSame(20, $lote->fresh()->cantidad_actual);
        $this->assertSame(1, MovimientoControlado::count());
    }

    public function test_tecnico_no_puede_firmar_ni_ver_el_libro(): void
    {
        [, , $lote] = $this->loteConIngreso();
        $tecnico = $this->usuario('tecnico');

        $this->actingAs($tecnico)->post(route('controlados.egreso'), [
            'lote_id' => $lote->id, 'cantidad' => 2, 'observacion' => 'x', 'password' => 'Clave#Segura2026',
        ])->assertForbidden();
        $this->actingAs($tecnico)->get(route('controlados.index'))->assertForbidden();

        $this->assertSame(20, $lote->fresh()->cantidad_actual);
    }

    public function test_administrador_ve_el_libro_pero_no_firma(): void
    {
        [, , $lote] = $this->loteConIngreso();
        $admin = $this->usuario('administrador');

        $this->actingAs($admin)->get(route('controlados.index'))->assertOk()->assertSee('solo lectura');
        $this->actingAs($admin)->post(route('controlados.egreso'), [
            'lote_id' => $lote->id, 'cantidad' => 1, 'observacion' => 'x', 'password' => 'Clave#Segura2026',
        ])->assertForbidden();
    }

    public function test_detecta_alteracion_directa_en_la_base_de_datos(): void
    {
        [$qf, , $lote] = $this->loteConIngreso();
        $libro = app(LibroControladosService::class);
        $lote->update(['cantidad_actual' => 15]);
        $libro->registrar($lote, 'egreso', 5, $qf, $qf, 'Uso');

        $this->assertNull($libro->verificarCadena());

        // Un atacante con acceso a la BD cambia la cantidad del primer registro.
        $primero = MovimientoControlado::orderBy('id')->first();
        DB::table('movimientos_controlados')->where('id', $primero->id)->update(['cantidad' => 2]);

        $this->assertSame($primero->id, $libro->verificarCadena());
    }

    public function test_el_modelo_impide_editar_el_libro(): void
    {
        $this->loteConIngreso();

        $this->expectException(LogicException::class);
        MovimientoControlado::first()->update(['cantidad' => 1]);
    }

    public function test_conteo_fisico_distinto_genera_alerta_de_descuadre(): void
    {
        [$qf, $p] = $this->loteConIngreso();

        $this->actingAs($qf)->post(route('controlados.conteo'), ['producto_id' => $p->id, 'cantidad_fisica' => 18])
            ->assertSessionHasErrors('conteo');

        $this->assertSame(1, Alerta::where('tipo', 'descuadre')->count());
    }

    public function test_descuadre_entre_libro_y_stock_genera_alerta(): void
    {
        [$qf, , $lote] = $this->loteConIngreso();

        // Alguien descuenta 3 unidades sin registrarlas en el libro...
        $lote->update(['cantidad_actual' => 17]);
        // ...y luego se registra un egreso legítimo de 2: el sistema esperaba 18 y encuentra 15.
        $lote->update(['cantidad_actual' => 15]);
        app(LibroControladosService::class)->registrar($lote, 'egreso', 2, $qf, $qf, 'Uso');

        $this->assertSame(1, Alerta::where('tipo', 'descuadre')->count());
    }
}
