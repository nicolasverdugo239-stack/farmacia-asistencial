<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConteoFisicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('firmar controlados');
    }

    public function rules(): array
    {
        return [
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad_fisica' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }
}
