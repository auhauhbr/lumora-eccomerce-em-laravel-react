<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SalvarProdutoRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $produto = $this->route('produto');

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'required',
                'string',
                'max:180',
                Rule::unique('products', 'slug')->ignore($produto?->id),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'brand' => ['nullable', 'string', 'max:80'],
            'condition' => ['required', 'string', Rule::in(['novo', 'usado', 'recondicionado'])],
            'price' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'stock' => ['required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image_urls' => ['nullable', 'array', 'max:8'],
            'image_urls.*' => ['required', 'url:http,https', 'max:2048'],
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
