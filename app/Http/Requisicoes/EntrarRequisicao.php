<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;

class EntrarRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
