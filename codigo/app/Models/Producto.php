<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $nombre
 * @property bool $es_controlado
 * @property int $stock_minimo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Lote> $lotes
 */
class Producto extends Model
{
    use Auditable;

    protected $fillable = [
        'categoria_id', 'codigo_barras', 'nombre', 'principio_activo',
        'presentacion', 'es_controlado', 'tipo_control', 'stock_minimo',
    ];

    protected function casts(): array
    {
        return ['es_controlado' => 'boolean', 'stock_minimo' => 'integer'];
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    /** @return HasMany<Lote, $this> */
    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function stockTotal(): int
    {
        return (int) $this->lotes()->sum('cantidad_actual');
    }
}
