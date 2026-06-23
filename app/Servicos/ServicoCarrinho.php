<?php

namespace App\Servicos;

use App\Modelos\Carrinho;
use App\Modelos\ItemCarrinho;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicoCarrinho
{
    public function obterOuCriar(Usuario $usuario): Carrinho
    {
        return Carrinho::query()->firstOrCreate([
            'user_id' => $usuario->id,
        ]);
    }

    public function adicionarItem(Usuario $usuario, int $produtoId, int $quantidade): Carrinho
    {
        return DB::transaction(function () use ($usuario, $produtoId, $quantidade): Carrinho {
            $produto = Produto::query()
                ->with('categoria')
                ->lockForUpdate()
                ->findOrFail($produtoId);

            $this->garantirProdutoCompravel($produto);

            $carrinho = $this->obterOuCriar($usuario);
            $item = $carrinho->itens()
                ->where('product_id', $produto->id)
                ->lockForUpdate()
                ->first();

            $quantidadeFinal = ($item?->quantity ?? 0) + $quantidade;
            $this->garantirEstoque($produto, $quantidadeFinal);

            if ($item) {
                $item->update([
                    'quantity' => $quantidadeFinal,
                ]);
            } else {
                $carrinho->itens()->create([
                    'product_id' => $produto->id,
                    'quantity' => $quantidadeFinal,
                    'unit_price' => $produto->price,
                ]);
            }

            return $this->carregar($carrinho);
        });
    }

    public function atualizarQuantidade(
        Usuario $usuario,
        ItemCarrinho $item,
        int $quantidade,
    ): Carrinho {
        $carrinho = $this->validarPertencimento($usuario, $item);
        $produto = $item->produto()->with('categoria')->firstOrFail();

        $this->garantirProdutoCompravel($produto);
        $this->garantirEstoque($produto, $quantidade);

        $item->update(['quantity' => $quantidade]);

        return $this->carregar($carrinho);
    }

    public function removerItem(Usuario $usuario, ItemCarrinho $item): Carrinho
    {
        $carrinho = $this->validarPertencimento($usuario, $item);
        $item->delete();

        return $this->carregar($carrinho);
    }

    public function limpar(Usuario $usuario): Carrinho
    {
        $carrinho = $this->obterOuCriar($usuario);
        $carrinho->itens()->delete();

        return $this->carregar($carrinho);
    }

    public function carregar(Carrinho $carrinho): Carrinho
    {
        return $carrinho->load([
            'itens' => fn ($consulta) => $consulta->orderBy('id'),
            'itens.produto.categoria',
        ]);
    }

    public function calcularSubtotal(Carrinho $carrinho): string
    {
        $totalEmCentavos = $carrinho->itens->sum(
            fn (ItemCarrinho $item) => (int) round(((float) $item->unit_price) * 100) * $item->quantity,
        );

        return number_format($totalEmCentavos / 100, 2, '.', '');
    }

    private function validarPertencimento(Usuario $usuario, ItemCarrinho $item): Carrinho
    {
        $carrinho = $item->carrinho;

        if (! $carrinho || $carrinho->user_id !== $usuario->id) {
            abort(404);
        }

        return $carrinho;
    }

    private function garantirProdutoCompravel(Produto $produto): void
    {
        if (! $produto->is_active || ! $produto->categoria?->is_active) {
            throw ValidationException::withMessages([
                'product_id' => ['Este produto não está disponível para compra.'],
            ]);
        }
    }

    private function garantirEstoque(Produto $produto, int $quantidade): void
    {
        if ($quantidade > $produto->stock) {
            throw ValidationException::withMessages([
                'quantity' => ["A quantidade solicitada ultrapassa o estoque disponível ({$produto->stock})."],
            ]);
        }
    }
}
