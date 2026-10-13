<?php

namespace Tests\Unit;

use App\Rules\Rut;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RutTest extends TestCase
{
    public static function rutsValidos(): array
    {
        return [['12.345.678-5'], ['12345678-5'], ['123456785'], ['7.000.000-8'], ['11.111.111-1']];
    }

    public static function rutsInvalidos(): array
    {
        return [['12.345.678-9'], ['abc'], [''], ['1-9'], ['12.345.678-K']];
    }

    #[DataProvider('rutsValidos')]
    public function test_acepta_rut_valido(string $rut): void
    {
        $this->assertTrue(Rut::valido($rut));
    }

    #[DataProvider('rutsInvalidos')]
    public function test_rechaza_rut_invalido(string $rut): void
    {
        $this->assertFalse(Rut::valido($rut));
    }

    public function test_calcula_digito_k_y_cero(): void
    {
        $this->assertSame('K', Rut::digitoVerificador('10000013'));
        $this->assertSame('0', Rut::digitoVerificador('10000021'));
    }

    public function test_normaliza_y_enmascara(): void
    {
        $this->assertSame('12345678-5', Rut::normalizar('12.345.678-5'));
        $this->assertSame('12.345.***-5', Rut::enmascarar('12345678-5'));
    }
}
