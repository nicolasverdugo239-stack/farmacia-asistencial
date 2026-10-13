<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemesaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recibir remesas');
    }

    public function rules(): array
    {
        return [
            'numero_guia' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/', 'unique:remesas,numero_guia'],
            'lineas' => ['required', 'array', 'min:1', 'max:100'],
            'lineas.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'lineas.*.numero_lote' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9\-]+$/'],
            'lineas.*.fecha_vencimiento' => ['required', 'date_format:Y-m-d', 'after:today'],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function messages(): array
    {
        return [
            'lineas.*.fecha_vencimiento.after' => 'No se puede recibir un lote vencido.',
        ];
    }
}
