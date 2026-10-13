<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $estado
 * @property \Illuminate\Support\Carbon $fecha_emision
 * @property \Illuminate\Support\Carbon $fecha_vencimiento
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RecetaDetalle> $detalles
 */
class Receta extends Model
{
    use Auditable;

    protected $fillable = [
        'folio', 'paciente_id', 'medico_id', 'programa_id', 'fecha_emision', 'fecha_vencimiento', 'estado',
    ];

    protected function casts(): array
    {
        return ['fecha_emision' => 'date', 'fecha_vencimiento' => 'date'];
    }

    /** @return BelongsTo<Paciente, $this> */
    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class);
    }

    /** @return BelongsTo<Medico, $this> */
    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class);
    }

    /** @return BelongsTo<Programa, $this> */
    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    /** @return HasMany<RecetaDetalle, $this> */
    public function detalles(): HasMany
    {
        return $this->hasMany(RecetaDetalle::class);
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'vigente'
            && $this->fecha_emision->lte(now()->startOfDay())
            && $this->fecha_vencimiento->gte(now()->startOfDay());
    }
}
