<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $fillable = ['nombre', 'sin_costo'];

    protected function casts(): array
    {
        return ['sin_costo' => 'boolean'];
    }
}
