<?php

namespace App\GraphQL\Campos;

use App\Modelos\Categoria;
use Illuminate\Database\Eloquent\Collection;

class ProdutosDaCategoriaCampo
{
    public function __invoke(Categoria $categoria): Collection
    {
        return $categoria->produtos()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
