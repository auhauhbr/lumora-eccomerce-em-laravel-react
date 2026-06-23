<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Modelos\Pedido;
use App\Servicos\ServicoPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagamentoControlador extends Controlador
{
    public function __construct(
        private readonly ServicoPagamento $servicoPagamento,
    ) {}

    public function criar(Request $requisicao, Pedido $pedido): JsonResponse
    {
        $pedido = $this->servicoPagamento->iniciar($requisicao->user(), $pedido);

        return response()->json([
            'mensagem' => 'Checkout criado com sucesso.',
            'dados' => [
                'order_id' => $pedido->id,
                'payment_provider' => $pedido->payment_provider,
                'payment_reference' => $pedido->payment_reference,
                'payment_url' => $pedido->payment_url,
                'payment_status' => $pedido->payment_status,
            ],
        ]);
    }

    public function status(Request $requisicao, Pedido $pedido): JsonResponse
    {
        if ($pedido->user_id !== $requisicao->user()->id) {
            abort(404);
        }

        return response()->json([
            'dados' => [
                'order_id' => $pedido->id,
                'order_status' => $pedido->status,
                'payment_status' => $pedido->payment_status,
                'payment_provider' => $pedido->payment_provider,
                'payment_url' => $pedido->payment_url,
                'paid_at' => $pedido->paid_at,
            ],
        ]);
    }
}
