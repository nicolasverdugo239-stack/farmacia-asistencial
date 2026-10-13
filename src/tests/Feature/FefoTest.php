<?php

namespace Tests\Feature;

use App\Services\FefoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\DatosFarmacia;
use Tests\TestCase;

class FefoTest extends TestCase
{
    use DatosFarmacia, RefreshDatabase;

    public function test_sugiere_el_lote_que_vence_primero(): void
    {
        $p = $this->producto();
        $this->lote($p, 'TARDE', 400, 100);
        $temprano = $this->lote($p, 'PRONTO', 30, 100);

        $this->assertTrue($temprano->is(app(FefoService::class)->loteSugerido($p->id, 10)));
    }

    public function test_ignora_lotes_vencidos(): void
    {
        $p = $this->producto();
        $this->lote($p, 'VENCIDO', -1, 100);
        $vigente = $this->lote($p, 'VIGENTE', 90, 100);

        $this->assertTrue($vigente->is(app(FefoService::class)->loteSugerido($p->id, 10)));
    }

    public function test_salta_lotes_sin_stock_suficiente(): void
    {
        $p = $this->producto();
        $this->lote($p, 'POCO', 10, 5);
        $suficiente = $this->lote($p, 'MUCHO', 200, 50);

        $this->assertTrue($suficiente->is(app(FefoService::class)->loteSugerido($p->id, 30)));
    }

    public function test_devuelve_null_si_no_hay_stock(): void
    {
        $p = $this->producto();
        $this->assertNull(app(FefoService::class)->loteSugerido($p->id, 1));
    }
}
