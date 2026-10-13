<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $producto_id
 * @property int $cantidad
 * @property-read Producto $producto
 */
class RecetaDetalle extends Model
{
    protected $fillable = ['receta_id', 'producto_id', 'dosis', 'cantidad'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer'];
    }

    /** @return BelongsTo<Receta, $this> */
    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
