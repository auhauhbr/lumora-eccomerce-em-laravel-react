<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Modelos\Categoria;
use Illuminate\Http\JsonResponse;

class CategoriaControlador extends Controlador
{
    public function listar(): JsonResponse
    {
        $categorias = Categoria::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'dados' => $categorias,
        ]);
    }
}
