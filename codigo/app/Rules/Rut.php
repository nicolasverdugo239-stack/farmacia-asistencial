<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Valida un RUT chileno con su dígito verificador (módulo 11).
 * Acepta "12.345.678-5", "12345678-5" o "123456785".
 */
class Rut implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::valido($value)) {
            $fail('El :attribute no es un RUT válido.');
        }
    }

    /** Deja el RUT como "12345678-5" (sin puntos, DV en mayúscula). */
    public static function normalizar(string $rut): string
    {
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');
        if (strlen($limpio) < 2) {
            return $limpio;
        }

        return substr($limpio, 0, -1).'-'.substr($limpio, -1);
    }

    public static function valido(string $rut): bool
    {
        $normal = self::normalizar($rut);
        if (! preg_match('/^(\d{7,8})-([\dK])$/', $normal, $m)) {
            return false;
        }

        return self::digitoVerificador($m[1]) === $m[2];
    }

    public static function digitoVerificador(string $cuerpo): string
    {
        $suma = 0;
        $factor = 2;
        for ($i = strlen($cuerpo) - 1; $i >= 0; $i--) {
            $suma += (int) $cuerpo[$i] * $factor;
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto = 11 - ($suma % 11);

        return match ($resto) {
            11 => '0',
            10 => 'K',
            default => (string) $resto,
        };
    }

    /** "12345678-5" → "12.345.***-5" para no exponer el RUT completo en pantalla. */
    public static function enmascarar(string $rut): string
    {
        $normal = self::normalizar($rut);
        [$cuerpo, $dv] = array_pad(explode('-', $normal), 2, '');
        $conPuntos = number_format((int) $cuerpo, 0, '', '.');
        $partes = explode('.', $conPuntos);
        $partes[count($partes) - 1] = '***';

        return implode('.', $partes).'-'.$dv;
    }
}
