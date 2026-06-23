<?php

namespace App\Http\Controladores\Admin;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\AjustarEstoqueRequisicao;
use App\Modelos\Produto;
use App\Servicos\ServicoEstoque;
use Illuminate\Http\JsonResponse;

class EstoqueAdminControlador extends Controlador
{
    public function __construct(
        private readonly ServicoEstoque $servicoEstoque,
    ) {}

    public function ajustar(
        AjustarEstoqueRequisicao $requisicao,
        Produto $produto,
    ): JsonResponse {
        $produto = $this->servicoEstoque->ajustar(
            $produto,
            $requisicao->integer('quantity'),
            $requisicao->string('reason')->value(),
        );

        return response()->json([
            'mensagem' => 'Estoque ajustado com sucesso.',
            'dados' => $produto,
        ]);
    }
}
