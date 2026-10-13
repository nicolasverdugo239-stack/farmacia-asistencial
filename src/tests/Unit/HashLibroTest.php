<?php

namespace Tests\Unit;

use App\Services\LibroControladosService;
use PHPUnit\Framework\TestCase;

class HashLibroTest extends TestCase
{
    private function datos(): array
    {
        return [
            'hash_anterior' => LibroControladosService::HASH_INICIAL,
            'lote_id' => 1,
            'tipo' => 'egreso',
            'cantidad' => 2,
            'saldo_resultante' => 18,
            'registrado_por' => 3,
            'autorizado_por' => 3,
            'created_at' => '2026-09-29 10:15:00',
        ];
    }

    public function test_el_hash_es_determinista(): void
    {
        $this->assertSame(
            LibroControladosService::calcularHash($this->datos()),
            LibroControladosService::calcularHash($this->datos())
        );
        $this->assertSame(64, strlen(LibroControladosService::calcularHash($this->datos())));
    }

    public function test_cambiar_cualquier_campo_cambia_el_hash(): void
    {
        $original = LibroControladosService::calcularHash($this->datos());

        foreach (['cantidad' => 3, 'saldo_resultante' => 17, 'autorizado_por' => 9, 'created_at' => '2026-09-29 10:16:00'] as $campo => $valor) {
            $alterado = array_merge($this->datos(), [$campo => $valor]);
            $this->assertNotSame($original, LibroControladosService::calcularHash($alterado), "Cambiar {$campo} debe cambiar el hash");
        }
    }
}
