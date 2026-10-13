<?php

namespace App\Models\Concerns;

use App\Services\AuditoriaService;

/**
 * Registra automáticamente en la bitácora de auditoría (MongoDB) cada
 * creación, modificación y eliminación del modelo. Los campos sensibles
 * se enmascaran para no copiar datos personales a la bitácora.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($modelo) {
            app(AuditoriaService::class)->registrar(
                'creado', $modelo, [], $modelo->atributosAuditables($modelo->getAttributes())
            );
        });

        static::updated(function ($modelo) {
            $cambios = $modelo->getChanges();
            unset($cambios['updated_at']);
            if ($cambios === []) {
                return;
            }
            $antes = array_intersect_key($modelo->getRawOriginal(), $cambios);
            app(AuditoriaService::class)->registrar(
                'actualizado', $modelo,
                $modelo->atributosAuditables($antes),
                $modelo->atributosAuditables($cambios)
            );
        });

        static::deleted(function ($modelo) {
            app(AuditoriaService::class)->registrar(
                'eliminado', $modelo, $modelo->atributosAuditables($modelo->getRawOriginal()), []
            );
        });
    }

    public function atributosAuditables(array $atributos): array
    {
        $protegidos = array_merge($this->getHidden(), $this->auditExcluir ?? []);
        foreach ($protegidos as $campo) {
            if (array_key_exists($campo, $atributos)) {
                $atributos[$campo] = '[protegido]';
            }
        }

        return $atributos;
    }
}
