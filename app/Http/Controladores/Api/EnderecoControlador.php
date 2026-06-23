<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\SalvarEnderecoRequisicao;
use App\Modelos\Endereco;
use App\Servicos\ServicoViaCep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnderecoControlador extends Controlador
{
    public function __construct(
        private readonly ServicoViaCep $servicoViaCep,
    ) {}

    public function consultarCep(string $cep): JsonResponse
    {
        return response()->json([
            'dados' => $this->servicoViaCep->buscar($cep),
        ]);
    }

    public function listar(Request $requisicao): JsonResponse
    {
        return response()->json([
            'dados' => $requisicao->user()
                ->enderecos()
                ->latest()
                ->get(),
        ]);
    }

    public function criar(SalvarEnderecoRequisicao $requisicao): JsonResponse
    {
        $endereco = $requisicao->user()
            ->enderecos()
            ->create($requisicao->validated());

        return response()->json([
            'mensagem' => 'Endereço salvo com sucesso.',
            'dados' => $endereco,
        ], 201);
    }

    public function excluir(Request $requisicao, Endereco $endereco): JsonResponse
    {
        if ($endereco->user_id !== $requisicao->user()->id) {
            abort(404);
        }

        if ($endereco->pedidos()->exists()) {
            return response()->json([
                'mensagem' => 'Este endereço está vinculado a um pedido e não pode ser excluído.',
            ], 422);
        }

        $endereco->delete();

        return response()->json(status: 204);
    }
}
