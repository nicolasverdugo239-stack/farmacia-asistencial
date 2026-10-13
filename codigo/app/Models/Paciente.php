<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Rules\Rut;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Datos personales cifrados en reposo (Ley 19.628). El RUT se guarda cifrado
 * y además como HMAC (rut_hash) para poder buscarlo sin descifrar.
 */
class Paciente extends Model
{
    use Auditable;

    protected $fillable = ['rut', 'nombre', 'telefono'];

    protected $hidden = ['rut', 'rut_hash', 'telefono'];

    protected function casts(): array
    {
        return ['rut' => 'encrypted', 'telefono' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::saving(function (Paciente $paciente) {
            if ($paciente->isDirty('rut')) {
                $paciente->rut_hash = self::hashRut($paciente->rut);
            }
        });
    }

    public static function hashRut(string $rut): string
    {
        return hash_hmac('sha256', Rut::normalizar($rut), (string) config('app.key'));
    }

    public static function buscarPorRut(string $rut): ?self
    {
        return self::where('rut_hash', self::hashRut($rut))->first();
    }

    /** RUT enmascarado para mostrar en pantalla: 12.345.***-5 */
    public function rutEnmascarado(): string
    {
        return Rut::enmascarar($this->rut);
    }

    public function recetas(): HasMany
    {
        return $this->hasMany(Receta::class);
    }
}
