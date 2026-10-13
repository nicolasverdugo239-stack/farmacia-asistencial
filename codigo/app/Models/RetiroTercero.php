<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetiroTercero extends Model
{
    use Auditable;

    protected $table = 'retiros_terceros';

    protected $fillable = ['dispensacion_id', 'nombre', 'rut', 'parentesco'];

    protected $hidden = ['rut'];

    protected function casts(): array
    {
        return ['rut' => 'encrypted'];
    }

    public function dispensacion(): BelongsTo
    {
        return $this->belongsTo(Dispensacion::class);
    }
}
