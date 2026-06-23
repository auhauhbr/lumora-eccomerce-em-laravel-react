<?php

namespace App\GraphQL\Consultas;

use App\Modelos\Produto;
use Illuminate\Database\Eloquent\Builder;

class ProdutoConsulta
{
    /** @param array{slug: string} $argumentos */
    public function __invoke(mixed $raiz, array $argumentos): ?Produto
    {
        return Produto::query()
            ->with('categoria')
            ->where('slug', $argumentos['slug'])
            ->where('is_active', true)
            ->whereHas('categoria', fn (Builder $consulta) => $consulta->where('is_active', true))
            ->first();
    }
}
