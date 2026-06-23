<?php

namespace App\Pagamentos;

use App\Modelos\Pedido;

interface InterfaceGatewayPagamento
{
    public function criarCheckout(Pedido $pedido): ResultadoCriacaoPagamento;

    public function consultarPagamento(string $referencia): ResultadoConsultaPagamento;
}
