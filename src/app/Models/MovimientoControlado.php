<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Fila del libro digital de controlados. Es de solo inserción: el modelo
 * impide modificar o borrar filas, y el hash encadenado permite detectar
 * cambios hechos directamente en la base de datos (ver LibroControladosService::verificarCadena).
 *
 * @property int $saldo_resultante
 * @property string $hash
 */
class MovimientoControlado extends Model
{
    protected $table = 'movimientos_controlados';

    protected $fillable = [
        'lote_id', 'tipo', 'cantidad', 'saldo_resultante', 'observacion',
        'registrado_por', 'autorizado_por', 'hash_anterior', 'hash', 'created_at', 'updated_at',
    ];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'saldo_resultante' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('El libro de controlados es de solo inserción.');
        });
        static::deleting(function () {
            throw new LogicException('El libro de controlados es de solo inserción.');
        });
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** @return BelongsTo<User, $this> */
    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
