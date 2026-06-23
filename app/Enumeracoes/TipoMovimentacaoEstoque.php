<?php

namespace App\Enumeracoes;

enum TipoMovimentacaoEstoque: string
{
    case Entrada = 'in';
    case Saida = 'out';
    case Ajuste = 'adjustment';
    case Cancelamento = 'cancellation';
}
