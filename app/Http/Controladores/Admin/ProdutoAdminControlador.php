<?php

namespace App\Http\Controladores\Admin;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\SalvarProdutoRequisicao;
use App\Modelos\Produto;
use Illuminate\Http\JsonResponse;

class ProdutoAdminControlador extends Controlador
{
    public function listar(): JsonResponse
    {
        return response()->json([
            'dados' => Produto::query()
                ->with('categoria')
                ->latest()
                ->get(),
        ]);
    }

    public function criar(SalvarProdutoRequisicao $requisicao): JsonResponse
    {
        $dados = $requisicao->validated();

        $produto = Produto::create($dados)->load('categoria');

        return response()->json([
            'mensagem' => 'Produto criado com sucesso.',
            'dados' => $produto,
        ], 201);
    }

    public function atualizar(
        SalvarProdutoRequisicao $requisicao,
        Produto $produto,
    ): JsonResponse {
        $dados = $requisicao->validated();

        $produto->update($dados);

        return response()->json([
            'mensagem' => 'Produto atualizado com sucesso.',
            'dados' => $produto->fresh()->load('categoria'),
        ]);
    }

    public function alternarAtivo(Produto $produto): JsonResponse
    {
        $produto->update([
            'is_active' => ! $produto->is_active,
        ]);

        return response()->json([
            'mensagem' => 'Status do produto atualizado.',
            'dados' => $produto->fresh(),
        ]);
    }
}
