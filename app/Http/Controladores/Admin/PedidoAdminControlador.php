<?php

namespace App\Http\Controladores\Admin;

use App\Enumeracoes\StatusPedido;
use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\AlterarStatusPedidoRequisicao;
use App\Modelos\Pedido;
use App\Servicos\ServicoPedido;
use Illuminate\Http\JsonResponse;

class PedidoAdminControlador extends Controlador
{
    public function __construct(
        private readonly ServicoPedido $servicoPedido,
    ) {}

    public function listar(): JsonResponse
    {
        return response()->json([
            'dados' => Pedido::query()
                ->with(['usuario:id,name,email', 'endereco', 'itens'])
                ->latest()
                ->get(),
        ]);
    }

    public function detalhar(Pedido $pedido): JsonResponse
    {
        return response()->json([
            'dados' => $pedido->load(['usuario:id,name,email', 'endereco', 'itens']),
        ]);
    }

    public function alterarStatus(
        AlterarStatusPedidoRequisicao $requisicao,
        Pedido $pedido,
    ): JsonResponse {
        $pedido = $this->servicoPedido->alterarStatusOperacional(
            $pedido,
            StatusPedido::from($requisicao->string('status')->value()),
        );

        return response()->json([
            'mensagem' => 'Status do pedido atualizado.',
            'dados' => $pedido,
        ]);
    }
}
