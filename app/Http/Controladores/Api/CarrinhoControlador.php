<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\AdicionarItemCarrinhoRequisicao;
use App\Http\Requisicoes\AtualizarItemCarrinhoRequisicao;
use App\Modelos\Carrinho;
use App\Modelos\ItemCarrinho;
use App\Servicos\ServicoCarrinho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CarrinhoControlador extends Controlador
{
    public function __construct(
        private readonly ServicoCarrinho $servicoCarrinho,
    ) {}

    public function mostrar(Request $requisicao): JsonResponse
    {
        $carrinho = $this->servicoCarrinho->carregar(
            $this->servicoCarrinho->obterOuCriar($requisicao->user()),
        );

        return $this->resposta($carrinho);
    }

    public function adicionar(AdicionarItemCarrinhoRequisicao $requisicao): JsonResponse
    {
        $carrinho = $this->servicoCarrinho->adicionarItem(
            $requisicao->user(),
            $requisicao->integer('product_id'),
            $requisicao->integer('quantity'),
        );

        return $this->resposta($carrinho, 'Produto adicionado ao carrinho.', 201);
    }

    public function atualizar(
        AtualizarItemCarrinhoRequisicao $requisicao,
        ItemCarrinho $item,
    ): JsonResponse {
        $carrinho = $this->servicoCarrinho->atualizarQuantidade(
            $requisicao->user(),
            $item,
            $requisicao->integer('quantity'),
        );

        return $this->resposta($carrinho, 'Quantidade atualizada.');
    }

    public function remover(Request $requisicao, ItemCarrinho $item): JsonResponse
    {
        $carrinho = $this->servicoCarrinho->removerItem($requisicao->user(), $item);

        return $this->resposta($carrinho, 'Item removido do carrinho.');
    }

    public function limpar(Request $requisicao): JsonResponse
    {
        $carrinho = $this->servicoCarrinho->limpar($requisicao->user());

        return $this->resposta($carrinho, 'Carrinho esvaziado.');
    }

    private function resposta(
        Carrinho $carrinho,
        ?string $mensagem = null,
        int $status = 200,
    ): JsonResponse {
        $dados = [
            'dados' => $carrinho,
            'subtotal' => $this->servicoCarrinho->calcularSubtotal($carrinho),
            'quantidade_itens' => $carrinho->itens->sum('quantity'),
        ];

        if ($mensagem) {
            $dados = ['mensagem' => $mensagem, ...$dados];
        }

        return response()->json($dados, $status);
    }
}
