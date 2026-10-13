<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarritoMovimiento extends Model
{
    protected $fillable = ['carrito_stock_id', 'tipo', 'cantidad', 'lote_id', 'user_id'];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(CarritoStock::class, 'carrito_stock_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
