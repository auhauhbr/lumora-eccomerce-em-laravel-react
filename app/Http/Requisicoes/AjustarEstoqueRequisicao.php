<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;

class AjustarEstoqueRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }
}
