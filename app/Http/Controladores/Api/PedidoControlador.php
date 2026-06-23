<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\CriarPedidoRequisicao;
use App\Modelos\Endereco;
use App\Modelos\Pedido;
use App\Servicos\ServicoPedido;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PedidoControlador extends Controlador
{
    public function __construct(
        private readonly ServicoPedido $servicoPedido,
    ) {}

    public function criar(CriarPedidoRequisicao $requisicao): JsonResponse
    {
        $endereco = Endereco::query()->findOrFail($requisicao->integer('address_id'));
        $pedido = $this->servicoPedido->criarDoCarrinho($requisicao->user(), $endereco);

        return response()->json([
            'mensagem' => 'Pedido criado. Aguardando início do pagamento.',
            'dados' => $pedido,
        ], 201);
    }

    public function listar(Request $requisicao): JsonResponse
    {
        return response()->json([
            'dados' => $requisicao->user()
                ->pedidos()
                ->with(['endereco', 'itens'])
                ->latest()
                ->get(),
        ]);
    }

    public function detalhar(Request $requisicao, Pedido $pedido): JsonResponse
    {
        if ($pedido->user_id !== $requisicao->user()->id) {
            abort(404);
        }

        return response()->json([
            'dados' => $this->servicoPedido->carregar($pedido),
        ]);
    }

    public function cancelar(Request $requisicao, Pedido $pedido): JsonResponse
    {
        return response()->json([
            'mensagem' => 'Pedido cancelado com sucesso.',
            'dados' => $this->servicoPedido->cancelar($requisicao->user(), $pedido),
        ]);
    }
}
