<?php

namespace App\Http\Controladores\Admin;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\SalvarMarcaRequisicao;
use App\Modelos\Marca;
use App\Modelos\Produto;
use Illuminate\Http\JsonResponse;

class MarcaAdminControlador extends Controlador
{
    public function listar(): JsonResponse
    {
        $marcas = Marca::query()
            ->orderBy('name')
            ->get()
            ->map(function (Marca $marca) {
                $marca->products_count = Produto::query()
                    ->where('brand', $marca->name)
                    ->count();

                return $marca;
            });

        return response()->json(['dados' => $marcas]);
    }

    public function criar(SalvarMarcaRequisicao $requisicao): JsonResponse
    {
        $marca = Marca::create($requisicao->validated());

        return response()->json([
            'mensagem' => 'Marca criada com sucesso.',
            'dados' => $marca,
        ], 201);
    }

    public function atualizar(SalvarMarcaRequisicao $requisicao, Marca $marca): JsonResponse
    {
        $nomeAnterior = $marca->name;

        $marca->update($requisicao->validated());

        if ($nomeAnterior !== $marca->name) {
            Produto::query()
                ->where('brand', $nomeAnterior)
                ->update(['brand' => $marca->name]);
        }

        return response()->json([
            'mensagem' => 'Marca atualizada com sucesso.',
            'dados' => $marca->fresh(),
        ]);
    }

    public function alternarAtiva(Marca $marca): JsonResponse
    {
        $marca->update(['is_active' => ! $marca->is_active]);

        return response()->json([
            'mensagem' => 'Status da marca atualizado.',
            'dados' => $marca->fresh(),
        ]);
    }
}
