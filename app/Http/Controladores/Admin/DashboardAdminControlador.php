<?php

namespace App\Http\Controladores\Admin;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Http\Controladores\Controlador;
use App\Modelos\EventoPagamento;
use App\Modelos\Pedido;
use App\Modelos\Produto;
use Illuminate\Http\JsonResponse;

class DashboardAdminControlador extends Controlador
{
    public function mostrar(): JsonResponse
    {
        $pedidosPagos = Pedido::query()
            ->where('payment_status', StatusPagamento::Aprovado);

        return response()->json([
            'dados' => [
                'total_vendas' => (clone $pedidosPagos)->count(),
                'faturamento_total' => $this->valor((clone $pedidosPagos)->sum('total')),
                'faturamento_mes' => $this->valor(
                    (clone $pedidosPagos)
                        ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                        ->sum('total'),
                ),
                'pedidos_aguardando_pagamento' => Pedido::query()
                    ->where('status', StatusPedido::AguardandoPagamento)
                    ->count(),
                'pedidos_pagos' => Pedido::query()
                    ->where('status', StatusPedido::Pago)
                    ->count(),
                'pedidos_enviados' => Pedido::query()
                    ->where('status', StatusPedido::Enviado)
                    ->count(),
                'produtos_ativos' => Produto::query()->where('is_active', true)->count(),
                'produtos_estoque_baixo' => Produto::query()
                    ->where('is_active', true)
                    ->where('stock', '<=', 5)
                    ->count(),
                'ultimos_pedidos' => Pedido::query()
                    ->with('usuario:id,name,email')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'ultimos_eventos_pagamento' => EventoPagamento::query()
                    ->with('pedido:id,status,payment_status')
                    ->latest()
                    ->limit(5)
                    ->get(),
                'estoque_baixo' => Produto::query()
                    ->with('categoria:id,name')
                    ->where('is_active', true)
                    ->where('stock', '<=', 5)
                    ->orderBy('stock')
                    ->limit(10)
                    ->get(),
            ],
        ]);
    }

    private function valor(int|float|string|null $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }
}
