<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\DispensacionDetalle;
use App\Models\RetiroTercero;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

/**
 * Pruebas de integración del flujo completo HTTP → controlador → servicio → base de datos.
 */
class DispensacionTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    public function test_dispensa_con_fefo_y_descuenta_stock(): void
    {
        $p = $this->producto();
        $pronto = $this->lote($p, 'L-PRONTO', 45, 118);
        $tarde = $this->lote($p, 'L-TARDE', 500, 240);
        $receta = $this->receta($p, 30);

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('dispensaciones.store', $receta), ['retira_tercero' => '0'])
            ->assertRedirect(route('dispensaciones.buscar'))
            ->assertSessionHas('ok');

        $this->assertSame(88, $pronto->fresh()->cantidad_actual);
        $this->assertSame(240, $tarde->fresh()->cantidad_actual);
        $this->assertSame('dispensada', $receta->fresh()->estado);
        $this->assertFalse(DispensacionDetalle::first()->fuera_de_fefo);
    }

    public function test_marca_cuando_se_elige_un_lote_fuera_de_fefo(): void
    {
        $p = $this->producto();
        $this->lote($p, 'L-PRONTO', 45, 100);
        $tarde = $this->lote($p, 'L-TARDE', 500, 100);
        $receta = $this->receta($p, 10);

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('dispensaciones.store', $receta), ['lotes' => [$p->id => $tarde->id]]);

        $this->assertTrue(DispensacionDetalle::first()->fuera_de_fefo);
    }

    public function test_rechaza_receta_vencida(): void
    {
        $p = $this->producto();
        $lote = $this->lote($p, 'L1', 100, 50);
        $receta = $this->receta($p, 10, diasVigencia: -1);

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('dispensaciones.store', $receta))
            ->assertSessionHasErrors('receta');

        $this->assertSame(50, $lote->fresh()->cantidad_actual);
    }

    public function test_no_permite_dispensar_dos_veces_la_misma_receta(): void
    {
        $p = $this->producto();
        $lote = $this->lote($p, 'L1', 100, 100);
        $receta = $this->receta($p, 10);
        $tecnico = $this->usuario('tecnico');

        $this->actingAs($tecnico)->post(route('dispensaciones.store', $receta));
        $this->actingAs($tecnico)->post(route('dispensaciones.store', $receta))->assertSessionHasErrors('receta');

        $this->assertSame(90, $lote->fresh()->cantidad_actual);
    }

    public function test_exige_datos_del_tercero_que_retira(): void
    {
        $p = $this->producto();
        $this->lote($p, 'L1', 100, 100);
        $receta = $this->receta($p, 10);

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('dispensaciones.store', $receta), ['retira_tercero' => '1'])
            ->assertSessionHasErrors(['tercero_nombre', 'tercero_rut', 'tercero_parentesco']);

        $this->assertSame('vigente', $receta->fresh()->estado);
    }

    public function test_guarda_el_tercero_con_rut_cifrado(): void
    {
        $p = $this->producto();
        $this->lote($p, 'L1', 100, 100);
        $receta = $this->receta($p, 10);

        $this->actingAs($this->usuario('tecnico'))->post(route('dispensaciones.store', $receta), [
            'retira_tercero' => '1',
            'tercero_nombre' => 'Camila González',
            'tercero_rut' => '11.111.111-1',
            'tercero_parentesco' => 'Hija',
        ])->assertSessionHas('ok');

        $tercero = RetiroTercero::first();
        $this->assertSame('11111111-1', $tercero->rut);
        // En la base de datos el RUT no está en texto plano.
        $this->assertStringNotContainsString('11111111', $tercero->getRawOriginal('rut'));
    }

    public function test_genera_alerta_de_stock_critico(): void
    {
        $p = $this->producto(stockMinimo: 100);
        $this->lote($p, 'L1', 100, 105);
        $receta = $this->receta($p, 10);

        $this->actingAs($this->usuario('tecnico'))->post(route('dispensaciones.store', $receta));

        $this->assertDatabaseHas('alertas', ['tipo' => 'stock_critico', 'producto_id' => $p->id]);
    }

    public function test_tecnico_no_puede_dispensar_controlados(): void
    {
        $p = $this->producto(controlado: true);
        $lote = $this->lote($p, 'MF1', 100, 20);
        $receta = $this->receta($p, 2);

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('dispensaciones.store', $receta))
            ->assertForbidden();

        $this->assertSame(20, $lote->fresh()->cantidad_actual);
        $this->assertSame(0, Alerta::count());
    }
}
