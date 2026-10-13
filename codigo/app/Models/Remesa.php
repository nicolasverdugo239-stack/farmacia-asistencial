<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Remesa extends Model
{
    use Auditable;

    protected $fillable = ['numero_guia', 'fecha_recepcion', 'recibido_por'];

    protected function casts(): array
    {
        return ['fecha_recepcion' => 'date'];
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }
}
