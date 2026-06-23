<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;

class AtualizarItemCarrinhoRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
