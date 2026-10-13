<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumoCarroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar carro');
    }

    public function rules(): array
    {
        return ['cantidad' => ['required', 'integer', 'min:1', 'max:1000']];
    }
}
