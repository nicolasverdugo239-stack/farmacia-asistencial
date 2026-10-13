<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $producto_id
 * @property int $cantidad_actual
 * @property \Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read Producto $producto
 */
class Lote extends Model
{
    use Auditable;

    protected $fillable = [
        'producto_id', 'remesa_id', 'numero_lote', 'fecha_vencimiento', 'cantidad_actual', 'ubicacion',
    ];

    protected function casts(): array
    {
        return ['fecha_vencimiento' => 'date', 'cantidad_actual' => 'integer'];
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Remesa, $this> */
    public function remesa(): BelongsTo
    {
        return $this->belongsTo(Remesa::class);
    }

    /** Lotes con stock y no vencidos, ordenados según FEFO. */
    public function scopeDisponiblesFefo(Builder $query, int $productoId): Builder
    {
        return $query->where('producto_id', $productoId)
            ->where('cantidad_actual', '>', 0)
            ->whereDate('fecha_vencimiento', '>=', now()->toDateString())
            ->orderBy('fecha_vencimiento')
            ->orderBy('id');
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento->lt(now()->startOfDay());
    }
}
