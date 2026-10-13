<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Escribe la bitácora de auditoría. Por defecto va a MongoDB (base no estructurada);
 * el driver "array" se usa en las pruebas y "log" como respaldo sin MongoDB.
 */
class AuditoriaService
{
    /** @var array<int, array<string, mixed>> */
    private static array $memoria = [];

    public function registrar(string $accion, ?Model $modelo = null, array $antes = [], array $despues = []): void
    {
        $evento = [
            'accion' => $accion,
            'modelo' => $modelo ? class_basename($modelo) : null,
            'modelo_id' => $modelo?->getKey(),
            'usuario_id' => Auth::id(),
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'antes' => $antes,
            'despues' => $despues,
            'fecha' => now()->toIso8601String(),
        ];

        try {
            match (config('auditoria.driver')) {
                'mongodb' => AuditLog::create($evento),
                'array' => self::$memoria[] = $evento,
                default => Log::channel(config('auditoria.canal_log', 'daily'))->info('auditoria', $evento),
            };
        } catch (Throwable $e) {
            // Si MongoDB no responde no se detiene la farmacia, pero queda un aviso crítico
            // en el log local con el evento completo para no perder la trazabilidad.
            Log::critical('No se pudo escribir la auditoría: '.$e->getMessage(), $evento);
        }
    }

    /** Solo para pruebas (driver "array"). */
    public static function eventos(): array
    {
        return self::$memoria;
    }

    public static function limpiar(): void
    {
        self::$memoria = [];
    }
}
