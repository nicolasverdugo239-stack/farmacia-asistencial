<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Dispensacion extends Model
{
    use Auditable;

    protected $table = 'dispensaciones';

    protected $fillable = ['receta_id', 'user_id'];

    public function receta(): BelongsTo
    {
        return $this->belongsTo(Receta::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DispensacionDetalle::class);
    }

    public function retiroTercero(): HasOne
    {
        return $this->hasOne(RetiroTercero::class);
    }
}
