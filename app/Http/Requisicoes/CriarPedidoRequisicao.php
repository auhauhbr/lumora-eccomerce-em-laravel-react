<?php

namespace App\Http\Requisicoes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CriarPedidoRequisicao extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where('user_id', $this->user()->id),
            ],
        ];
    }
}
