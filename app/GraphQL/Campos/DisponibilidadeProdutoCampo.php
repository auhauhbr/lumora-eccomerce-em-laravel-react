<?php

namespace App\GraphQL\Campos;

use App\Modelos\Produto;

class DisponibilidadeProdutoCampo
{
    public function __invoke(Produto $produto): bool
    {
        return $produto->estaDisponivel();
    }
}
