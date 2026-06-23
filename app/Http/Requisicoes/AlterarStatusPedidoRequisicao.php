<?php

namespace App\Http\Requisicoes;

use App\Enumeracoes\StatusPedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AlterarStatusPedidoRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusPedido::class)],
        ];
    }
}
