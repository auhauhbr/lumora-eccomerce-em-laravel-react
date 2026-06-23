<?php

namespace App\Enumeracoes;

enum StatusPedido: string
{
    case AguardandoPagamento = 'pending_payment';
    case Pago = 'paid';
    case EmProcessamento = 'processing';
    case Enviado = 'shipped';
    case Entregue = 'delivered';
    case Cancelado = 'cancelled';
    case Expirado = 'expired';
}
