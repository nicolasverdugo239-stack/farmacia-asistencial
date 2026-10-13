<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispensacionDetalle extends Model
{
    protected $fillable = ['dispensacion_id', 'lote_id', 'cantidad', 'fuera_de_fefo'];

    protected function casts(): array
    {
        return ['cantidad' => 'integer', 'fuera_de_fefo' => 'boolean'];
    }

    public function dispensacion(): BelongsTo
    {
        return $this->belongsTo(Dispensacion::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
