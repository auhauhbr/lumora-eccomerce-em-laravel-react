<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Modelos\Marca;
use App\Modelos\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProdutoControlador extends Controlador
{
    public function listar(Request $requisicao): JsonResponse
    {
        $produtos = Produto::query()
            ->with('categoria')
            ->where('is_active', true)
            ->whereHas('categoria', fn (Builder $consulta) => $consulta->where('is_active', true))
            ->when($requisicao->filled('search'), function (Builder $consulta) use ($requisicao): void {
                $busca = '%'.$requisicao->string('search')->trim()->value().'%';

                $consulta->where(function (Builder $subconsulta) use ($busca): void {
                    $subconsulta
                        ->where('name', 'like', $busca)
                        ->orWhere('description', 'like', $busca);
                });
            })
            ->when($requisicao->filled('category'), function (Builder $consulta) use ($requisicao): void {
                $consulta->whereHas('categoria', function (Builder $categoria) use ($requisicao): void {
                    $categoria->where('slug', $requisicao->string('category'));
                });
            })
            ->when($requisicao->filled('brand'), function (Builder $consulta) use ($requisicao): void {
                $consulta->where('brand', $requisicao->string('brand'));
            })
            ->when($requisicao->filled('condition'), function (Builder $consulta) use ($requisicao): void {
                $consulta->where('condition', $requisicao->string('condition'));
            })
            ->when($requisicao->filled('min_price'), fn (Builder $consulta) => $consulta
                ->where('price', '>=', $requisicao->input('min_price')))
            ->when($requisicao->filled('max_price'), fn (Builder $consulta) => $consulta
                ->where('price', '<=', $requisicao->input('max_price')))
            ->when($requisicao->boolean('in_stock'), fn (Builder $consulta) => $consulta
                ->where('stock', '>', 0));

        $ordenacao = match ($requisicao->string('sort')->value()) {
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'name_desc' => ['name', 'desc'],
            default => ['name', 'asc'],
        };

        $paginacao = $produtos
            ->orderBy($ordenacao[0], $ordenacao[1])
            ->paginate(min(max($requisicao->integer('per_page', 12), 1), 50));

        return response()->json([
            'dados' => $paginacao->items(),
            'filtros' => [
                'marcas' => Marca::query()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name')
                    ->values(),
            ],
            'paginacao' => [
                'pagina_atual' => $paginacao->currentPage(),
                'ultima_pagina' => $paginacao->lastPage(),
                'por_pagina' => $paginacao->perPage(),
                'total' => $paginacao->total(),
            ],
        ]);
    }

    public function detalhar(Produto $produto): JsonResponse
    {
        $produto->load('categoria');

        abort_unless(
            $produto->is_active && $produto->categoria->is_active,
            404,
            'Produto não encontrado.',
        );

        return response()->json([
            'dados' => $produto,
            'disponivel' => $produto->estaDisponivel(),
        ]);
    }
}

