<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar usuarios');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('rut')) {
            $this->merge(['rut' => Rut::normalizar($this->string('rut')->toString())]);
        }
    }

    public function rules(): array
    {
        return [
            'rut' => ['required', 'string', 'max:12', new Rut, 'unique:users,rut'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['required', Rule::in(['administrador', 'tecnico', 'quimico'])],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
