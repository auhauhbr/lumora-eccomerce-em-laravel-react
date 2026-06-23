<?php

namespace App\GraphQL\Consultas;

use App\Modelos\Produto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ProdutosConsulta
{
    /** @param array<string, mixed> $argumentos */
    public function __invoke(mixed $raiz, array $argumentos): Collection
    {
        return Produto::query()
            ->with('categoria')
            ->where('is_active', true)
            ->whereHas('categoria', fn (Builder $consulta) => $consulta->where('is_active', true))
            ->when($argumentos['search'] ?? null, function (Builder $consulta, string $busca): void {
                $consulta->where(function (Builder $subconsulta) use ($busca): void {
                    $subconsulta
                        ->where('name', 'like', "%{$busca}%")
                        ->orWhere('description', 'like', "%{$busca}%");
                });
            })
            ->when($argumentos['category'] ?? null, function (Builder $consulta, string $categoria): void {
                $consulta->whereHas(
                    'categoria',
                    fn (Builder $subconsulta) => $subconsulta->where('slug', $categoria),
                );
            })
            ->when(
                array_key_exists('min_price', $argumentos),
                fn (Builder $consulta) => $consulta->where('price', '>=', $argumentos['min_price']),
            )
            ->when(
                array_key_exists('max_price', $argumentos),
                fn (Builder $consulta) => $consulta->where('price', '<=', $argumentos['max_price']),
            )
            ->when(
                ($argumentos['in_stock'] ?? false) === true,
                fn (Builder $consulta) => $consulta->where('stock', '>', 0),
            )
            ->orderBy('name')
            ->get();
    }
}
