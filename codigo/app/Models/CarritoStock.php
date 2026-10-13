<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $producto_id
 * @property int $cantidad
 * @property int $stock_minimo
 * @property-read CarritoSeccion $seccion
 * @property-read Producto $producto
 */
class CarritoStock extends Model
{
    use Auditable;

    protected $table = 'carrito_stock';

    protected $fillable = ['carrito_seccion_id', 'producto_id', 'cantidad', 'stock_minimo', 'stock_maximo'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'stock_minimo' => 'integer', 'stock_maximo' => 'integer'];
    }

    /** @return BelongsTo<CarritoSeccion, $this> */
    public function seccion(): BelongsTo
    {
        return $this->belongsTo(CarritoSeccion::class, 'carrito_seccion_id');
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return HasMany<CarritoMovimiento, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(CarritoMovimiento::class);
    }

    public function bajoMinimo(): bool
    {
        return $this->cantidad < $this->stock_minimo;
    }
}
