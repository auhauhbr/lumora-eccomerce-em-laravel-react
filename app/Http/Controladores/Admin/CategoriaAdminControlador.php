<?php

namespace App\Http\Controladores\Admin;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\SalvarCategoriaRequisicao;
use App\Modelos\Categoria;
use Illuminate\Http\JsonResponse;

class CategoriaAdminControlador extends Controlador
{
    public function listar(): JsonResponse
    {
        return response()->json([
            'dados' => Categoria::query()
                ->withCount('produtos')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function criar(SalvarCategoriaRequisicao $requisicao): JsonResponse
    {
        $dados = $requisicao->validated();

        $categoria = Categoria::create($dados);

        return response()->json([
            'mensagem' => 'Categoria criada com sucesso.',
            'dados' => $categoria,
        ], 201);
    }

    public function atualizar(
        SalvarCategoriaRequisicao $requisicao,
        Categoria $categoria,
    ): JsonResponse {
        $dados = $requisicao->validated();

        $categoria->update($dados);

        return response()->json([
            'mensagem' => 'Categoria atualizada com sucesso.',
            'dados' => $categoria->fresh(),
        ]);
    }

    public function alternarAtiva(Categoria $categoria): JsonResponse
    {
        $categoria->update([
            'is_active' => ! $categoria->is_active,
        ]);

        return response()->json([
            'mensagem' => 'Status da categoria atualizado.',
            'dados' => $categoria->fresh(),
        ]);
    }

    public function excluir(Categoria $categoria): JsonResponse
    {
        if ($categoria->produtos()->exists()) {
            return response()->json([
                'mensagem' => 'A categoria possui produtos e não pode ser excluída.',
            ], 422);
        }

        $categoria->delete();

        return response()->json(status: 204);
    }
}
