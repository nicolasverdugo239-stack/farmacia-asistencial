<?php

namespace App\Http\Requests;

use App\Rules\Rut;
use Illuminate\Foundation\Http\FormRequest;

class DispensarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dispensar');
    }

    public function rules(): array
    {
        return [
            'lotes' => ['nullable', 'array'],
            'lotes.*' => ['integer', 'exists:lotes,id'],
            'retira_tercero' => ['boolean'],
            'tercero_nombre' => ['required_if_accepted:retira_tercero', 'nullable', 'string', 'max:120'],
            'tercero_rut' => ['required_if_accepted:retira_tercero', 'nullable', 'string', 'max:12', new Rut],
            'tercero_parentesco' => ['required_if_accepted:retira_tercero', 'nullable', 'string', 'max:40'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tercero_nombre' => 'nombre de quien retira',
            'tercero_rut' => 'RUT de quien retira',
            'tercero_parentesco' => 'parentesco',
        ];
    }

    /** @return array{nombre: string, rut: string, parentesco: string}|null */
    public function tercero(): ?array
    {
        if (! $this->boolean('retira_tercero')) {
            return null;
        }

        return [
            'nombre' => $this->string('tercero_nombre')->trim()->toString(),
            'rut' => Rut::normalizar($this->string('tercero_rut')->toString()),
            'parentesco' => $this->string('tercero_parentesco')->trim()->toString(),
        ];
    }
}
