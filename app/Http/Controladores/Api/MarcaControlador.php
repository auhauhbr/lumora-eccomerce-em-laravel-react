<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Modelos\Marca;
use Illuminate\Http\JsonResponse;

class MarcaControlador extends Controlador
{
    public function listar(): JsonResponse
    {
        return response()->json([
            'dados' => Marca::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
