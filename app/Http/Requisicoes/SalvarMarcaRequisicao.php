<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalvarMarcaRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $marca = $this->route('marca');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('brands', 'slug')->ignore($marca?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
        ]);
    }
}
