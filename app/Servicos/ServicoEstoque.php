<?php

namespace App\Servicos;

use App\Enumeracoes\TipoMovimentacaoEstoque;
use App\Modelos\MovimentacaoEstoque;
use App\Modelos\Pedido;
use App\Modelos\Produto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicoEstoque
{
    public function reduzirParaPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('itens');

        foreach ($pedido->itens as $item) {
            $jaMovimentado = MovimentacaoEstoque::query()
                ->where('product_id', $item->product_id)
                ->where('order_id', $pedido->id)
                ->where('type', TipoMovimentacaoEstoque::Saida)
                ->exists();

            if ($jaMovimentado) {
                continue;
            }

            $produto = Produto::query()->lockForUpdate()->findOrFail($item->product_id);

            if ($produto->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => ["Estoque insuficiente para {$item->product_name}."],
                ]);
            }

            $produto->decrement('stock', $item->quantity);

            MovimentacaoEstoque::create([
                'product_id' => $produto->id,
                'order_id' => $pedido->id,
                'type' => TipoMovimentacaoEstoque::Saida,
                'quantity' => -$item->quantity,
                'reason' => 'Pagamento aprovado do pedido '.$pedido->id,
            ]);
        }
    }

    public function restaurarParaPedido(Pedido $pedido): void
    {
        $pedido->loadMissing('itens');

        foreach ($pedido->itens as $item) {
            $jaRestaurado = MovimentacaoEstoque::query()
                ->where('product_id', $item->product_id)
                ->where('order_id', $pedido->id)
                ->where('type', TipoMovimentacaoEstoque::Cancelamento)
                ->exists();

            if ($jaRestaurado) {
                continue;
            }

            $produto = Produto::query()->lockForUpdate()->findOrFail($item->product_id);
            $produto->increment('stock', $item->quantity);

            MovimentacaoEstoque::create([
                'product_id' => $produto->id,
                'order_id' => $pedido->id,
                'type' => TipoMovimentacaoEstoque::Cancelamento,
                'quantity' => $item->quantity,
                'reason' => 'Estoque devolvido pelo cancelamento do pedido '.$pedido->id,
            ]);
        }
    }

    public function ajustar(Produto $produto, int $quantidade, string $motivo): Produto
    {
        return DB::transaction(function () use ($produto, $quantidade, $motivo): Produto {
            $produto = Produto::query()->lockForUpdate()->findOrFail($produto->id);
            $estoqueFinal = $produto->stock + $quantidade;

            if ($estoqueFinal < 0) {
                throw ValidationException::withMessages([
                    'quantity' => ['O ajuste deixaria o estoque negativo.'],
                ]);
            }

            $produto->update(['stock' => $estoqueFinal]);

            MovimentacaoEstoque::create([
                'product_id' => $produto->id,
                'order_id' => null,
                'type' => TipoMovimentacaoEstoque::Ajuste,
                'quantity' => $quantidade,
                'reason' => $motivo,
            ]);

            return $produto->fresh();
        });
    }
}
