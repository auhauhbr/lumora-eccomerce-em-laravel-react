<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalvarCategoriaRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoria = $this->route('categoria');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:140',
                Rule::unique('categories', 'slug')->ignore($categoria?->id),
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
