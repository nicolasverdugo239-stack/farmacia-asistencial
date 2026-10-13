<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrito extends Model
{
    protected $fillable = ['codigo', 'servicio'];

    /** @return HasMany<CarritoSeccion, $this> */
    public function secciones(): HasMany
    {
        return $this->hasMany(CarritoSeccion::class);
    }
}
