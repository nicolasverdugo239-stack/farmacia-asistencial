<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Egreso manual de un controlado (merma, uso interno, etc.).
 * La firma exige volver a ingresar la contraseña (regla current_password).
 */
class EgresoControladoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('firmar controlados');
    }

    public function rules(): array
    {
        return [
            'lote_id' => ['required', 'integer', 'exists:lotes,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
            'observacion' => ['required', 'string', 'max:255'],
            'password' => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return ['password.current_password' => 'La contraseña no es correcta. La firma no se registró.'];
    }
}
