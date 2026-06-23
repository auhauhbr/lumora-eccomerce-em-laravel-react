<?php

namespace App\Http\Controladores\Api;

use App\Enumeracoes\PapelUsuario;
use App\Http\Controladores\Controlador;
use App\Http\Requisicoes\EntrarRequisicao;
use App\Http\Requisicoes\RegistrarUsuarioRequisicao;
use App\Modelos\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AutenticacaoControlador extends Controlador
{
    public function registrar(RegistrarUsuarioRequisicao $requisicao): JsonResponse
    {
        $usuario = Usuario::create([
            ...$requisicao->safe()->only(['name', 'email', 'password']),
            'role' => PapelUsuario::Cliente,
        ]);

        return response()->json([
            'mensagem' => 'Conta criada com sucesso.',
            'usuario' => $usuario,
            'token' => $usuario->createToken('lumora-api')->plainTextToken,
        ], 201);
    }

    public function entrar(EntrarRequisicao $requisicao): JsonResponse
    {
        $usuario = Usuario::query()
            ->where('email', $requisicao->string('email'))
            ->first();

        if (! $usuario || ! Hash::check($requisicao->string('password'), $usuario->password)) {
            throw ValidationException::withMessages([
                'email' => ['E-mail ou senha inválidos.'],
            ]);
        }

        return response()->json([
            'mensagem' => 'Login realizado com sucesso.',
            'usuario' => $usuario,
            'token' => $usuario->createToken(
                $requisicao->string('device_name')->value() ?: 'lumora-api'
            )->plainTextToken,
        ]);
    }

    public function sair(Request $requisicao): JsonResponse
    {
        $requisicao->user()->currentAccessToken()?->delete();

        return response()->json([
            'mensagem' => 'Sessão encerrada com sucesso.',
        ]);
    }

    public function eu(Request $requisicao): JsonResponse
    {
        return response()->json([
            'usuario' => $requisicao->user(),
        ]);
    }
}
