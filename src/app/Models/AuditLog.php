<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use LogicException;

/**
 * Evento de la bitácora de auditoría, guardado en MongoDB (colección "auditoria").
 * Cada evento tiene una forma distinta (antes/después según el modelo), por eso
 * una base documental encaja mejor que una tabla relacional. Solo inserción.
 */
class AuditLog extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'auditoria';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('La bitácora de auditoría es de solo inserción.');
        });
        static::deleting(function () {
            throw new LogicException('La bitácora de auditoría es de solo inserción.');
        });
    }
}
