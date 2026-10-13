<?php

namespace Tests\Feature;

use App\Models\Carrito;
use App\Models\CarritoSeccion;
use App\Models\CarritoStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

class CarroTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    private CarritoStock $paro;

    private CarritoStock $unidosis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();

        $p = $this->producto();
        $carro = Carrito::create(['codigo' => 'BOX-1', 'servicio' => 'Urgencia']);
        $sParo = CarritoSeccion::create(['carrito_id' => $carro->id, 'tipo' => 'paro']);
        $sUni = CarritoSeccion::create(['carrito_id' => $carro->id, 'tipo' => 'unidosis']);
        $this->paro = CarritoStock::create(['carrito_seccion_id' => $sParo->id, 'producto_id' => $p->id, 'cantidad' => 5, 'stock_minimo' => 4, 'stock_maximo' => 6]);
        $this->unidosis = CarritoStock::create(['carrito_seccion_id' => $sUni->id, 'producto_id' => $p->id, 'cantidad' => 10, 'stock_minimo' => 5, 'stock_maximo' => 12]);
        $this->lote($p, 'BODEGA-1', 200, 100);
    }

    public function test_consumo_descuenta_solo_su_seccion(): void
    {
        $this->actingAs($this->usuario('tecnico'))
            ->post(route('carros.consumo', $this->paro), ['cantidad' => 1])
            ->assertSessionHas('ok');

        $this->assertSame(4, $this->paro->fresh()->cantidad);
        $this->assertSame(10, $this->unidosis->fresh()->cantidad);
    }

    public function test_bajo_el_minimo_genera_alerta_de_carro(): void
    {
        $this->actingAs($this->usuario('tecnico'))->post(route('carros.consumo', $this->paro), ['cantidad' => 2]);

        $this->assertDatabaseHas('alertas', ['tipo' => 'carro']);
    }

    public function test_reposicion_llena_la_seccion_desde_bodega(): void
    {
        $tecnico = $this->usuario('tecnico');
        $this->actingAs($tecnico)->post(route('carros.consumo', $this->paro), ['cantidad' => 3]);
        $this->actingAs($tecnico)->post(route('carros.reposicion', $this->paro))->assertSessionHas('ok');

        $this->assertSame(6, $this->paro->fresh()->cantidad);
        $this->assertDatabaseHas('lotes', ['numero_lote' => 'BODEGA-1', 'cantidad_actual' => 96]);
    }

    public function test_no_permite_consumir_mas_de_lo_que_hay(): void
    {
        $this->actingAs($this->usuario('tecnico'))
            ->post(route('carros.consumo', $this->paro), ['cantidad' => 50])
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(5, $this->paro->fresh()->cantidad);
    }
}
