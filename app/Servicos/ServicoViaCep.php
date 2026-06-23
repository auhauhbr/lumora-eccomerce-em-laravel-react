<?php

namespace App\Servicos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ServicoViaCep
{
    /** @return array<string, string|null> */
    public function buscar(string $cep): array
    {
        $cep = $this->normalizar($cep);

        if (strlen($cep) !== 8) {
            throw ValidationException::withMessages([
                'cep' => ['Informe um CEP válido com 8 dígitos.'],
            ]);
        }

        try {
            $resposta = Http::baseUrl(config('services.viacep.url'))
                ->acceptJson()
                ->withOptions([
                    'verify' => config('services.viacep.ca_bundle'),
                ])
                ->timeout(8)
                ->retry(2, 200)
                ->get("/ws/{$cep}/json/");
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'cep' => ['Não foi possível consultar o CEP agora. Tente novamente.'],
            ]);
        }

        if ($resposta->failed() || $resposta->json('erro')) {
            throw ValidationException::withMessages([
                'cep' => ['CEP não encontrado.'],
            ]);
        }

        return [
            'zip_code' => $cep,
            'street' => $resposta->json('logradouro'),
            'complement' => $resposta->json('complemento') ?: null,
            'neighborhood' => $resposta->json('bairro'),
            'city' => $resposta->json('localidade'),
            'state' => $resposta->json('uf'),
        ];
    }

    public function normalizar(string $cep): string
    {
        return preg_replace('/\D/', '', $cep) ?? '';
    }
}
