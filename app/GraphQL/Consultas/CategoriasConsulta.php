<?php

namespace App\GraphQL\Consultas;

use App\Modelos\Categoria;
use Illuminate\Database\Eloquent\Collection;

class CategoriasConsulta
{
    public function __invoke(): Collection
    {
        return Categoria::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
