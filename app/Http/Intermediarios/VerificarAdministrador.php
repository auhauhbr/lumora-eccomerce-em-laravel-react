<?php

namespace App\Http\Intermediarios;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarAdministrador
{
    public function handle(Request $requisicao, Closure $proximo): Response|JsonResponse
    {
        if (! $requisicao->user()?->ehAdministrador()) {
            return response()->json([
                'mensagem' => 'Acesso permitido apenas para administradores.',
            ], 403);
        }

        return $proximo($requisicao);
    }
}
