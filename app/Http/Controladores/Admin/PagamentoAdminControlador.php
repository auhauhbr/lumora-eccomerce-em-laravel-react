<?php

namespace App\Http\Controladores\Admin;

use App\Http\Controladores\Controlador;
use App\Modelos\EventoPagamento;
use App\Modelos\MovimentacaoEstoque;
use Illuminate\Http\JsonResponse;

class PagamentoAdminControlador extends Controlador
{
    public function eventos(): JsonResponse
    {
        return response()->json([
            'dados' => EventoPagamento::query()
                ->with('pedido:id,user_id,status,payment_status')
                ->latest()
                ->get(),
        ]);
    }

    public function movimentacoesEstoque(): JsonResponse
    {
        return response()->json([
            'dados' => MovimentacaoEstoque::query()
                ->with(['produto:id,name,stock', 'pedido:id,status'])
                ->latest()
                ->get(),
        ]);
    }
}
