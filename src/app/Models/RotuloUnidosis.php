<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RotuloUnidosis extends Model
{
    protected $table = 'rotulos_unidosis';

    protected $fillable = ['paciente_id', 'lote_id', 'dosis', 'horario', 'preparado_por'];

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
