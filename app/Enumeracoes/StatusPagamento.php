<?php

namespace App\Enumeracoes;

enum StatusPagamento: string
{
    case Pendente = 'pending';
    case Aprovado = 'approved';
    case Rejeitado = 'rejected';
    case Cancelado = 'cancelled';
    case Reembolsado = 'refunded';
    case Expirado = 'expired';
}
