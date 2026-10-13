<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarritoSeccion extends Model
{
    protected $table = 'carrito_secciones';

    protected $fillable = ['carrito_id', 'tipo'];

    /** @return BelongsTo<Carrito, $this> */
    public function carrito(): BelongsTo
    {
        return $this->belongsTo(Carrito::class);
    }

    /** @return HasMany<CarritoStock, $this> */
    public function stock(): HasMany
    {
        return $this->hasMany(CarritoStock::class);
    }

    public function nombre(): string
    {
        return $this->tipo === 'paro' ? 'Carro de Paro' : 'Unidosis';
    }
}
