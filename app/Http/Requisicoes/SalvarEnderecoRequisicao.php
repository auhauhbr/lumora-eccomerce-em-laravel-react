<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;

class SalvarEnderecoRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zip_code' => ['required', 'digits:8'],
            'street' => ['required', 'string', 'max:180'],
            'number' => ['required', 'string', 'max:30'],
            'complement' => ['nullable', 'string', 'max:120'],
            'neighborhood' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', 'string', 'size:2'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'zip_code' => preg_replace('/\D/', '', (string) $this->input('zip_code')),
            'state' => strtoupper((string) $this->input('state')),
        ]);
    }
}
