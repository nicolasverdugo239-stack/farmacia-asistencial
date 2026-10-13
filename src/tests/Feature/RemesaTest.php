<?php

namespace Tests\Feature;

use App\Models\Lote;
use App\Models\MovimientoControlado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

class RemesaTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararRoles();
    }

    public function test_recibe_remesa_y_crea_lotes(): void
    {
        $p = $this->producto();

        $this->actingAs($this->usuario('tecnico'))->post(route('remesas.store'), [
            'numero_guia' => 'GD-2026-0931',
            'lineas' => [
                ['producto_id' => $p->id, 'numero_lote' => 'L-1', 'fecha_vencimiento' => now()->addYear()->toDateString(), 'cantidad' => 120],
                ['producto_id' => $p->id, 'numero_lote' => 'L-2', 'fecha_vencimiento' => now()->addYears(2)->toDateString(), 'cantidad' => 80],
            ],
        ])->assertSessionHas('ok');

        $this->assertSame(200, $p->stockTotal());
    }

    public function test_rechaza_lote_vencido(): void
    {
        $p = $this->producto();

        $this->actingAs($this->usuario('tecnico'))->post(route('remesas.store'), [
            'numero_guia' => 'GD-1',
            'lineas' => [['producto_id' => $p->id, 'numero_lote' => 'L-1', 'fecha_vencimiento' => now()->subDay()->toDateString(), 'cantidad' => 10]],
        ])->assertSessionHasErrors('lineas.0.fecha_vencimiento');

        $this->assertSame(0, Lote::count());
    }

    public function test_controlados_los_recibe_el_quimico_y_quedan_en_el_libro(): void
    {
        $p = $this->producto(controlado: true);
        $linea = ['producto_id' => $p->id, 'numero_lote' => 'MF-1', 'fecha_vencimiento' => now()->addYear()->toDateString(), 'cantidad' => 20];

        $this->actingAs($this->usuario('tecnico'))
            ->post(route('remesas.store'), ['numero_guia' => 'GD-2', 'lineas' => [$linea]])
            ->assertSessionHasErrors('lineas');

        $this->actingAs($this->usuario('quimico'))
            ->post(route('remesas.store'), ['numero_guia' => 'GD-3', 'lineas' => [$linea]])
            ->assertSessionHas('ok');

        $this->assertSame(1, MovimientoControlado::where('tipo', 'ingreso')->count());
    }

    public function test_rechaza_codigos_con_caracteres_peligrosos(): void
    {
        $p = $this->producto();

        $this->actingAs($this->usuario('tecnico'))->post(route('remesas.store'), [
            'numero_guia' => "GD'; DROP TABLE lotes;--",
            'lineas' => [['producto_id' => $p->id, 'numero_lote' => '<script>', 'fecha_vencimiento' => now()->addYear()->toDateString(), 'cantidad' => 1]],
        ])->assertSessionHasErrors(['numero_guia', 'lineas.0.numero_lote']);
    }
}
