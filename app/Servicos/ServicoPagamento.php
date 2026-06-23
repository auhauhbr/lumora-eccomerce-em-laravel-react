<?php

namespace App\Servicos;

use App\Enumeracoes\StatusPedido;
use App\Modelos\Pedido;
use App\Modelos\Usuario;
use App\Pagamentos\InterfaceGatewayPagamento;
use App\Pagamentos\ResultadoCriacaoPagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicoPagamento
{
    public function __construct(
        private readonly InterfaceGatewayPagamento $gateway,
    ) {}

    public function iniciar(Usuario $usuario, Pedido $pedido): Pedido
    {
        if ($pedido->user_id !== $usuario->id) {
            abort(404);
        }

        if ($pedido->status === StatusPedido::Pago) {
            throw ValidationException::withMessages([
                'payment' => ['Este pedido já está pago.'],
            ]);
        }

        if ($pedido->status === StatusPedido::Cancelado) {
            throw ValidationException::withMessages([
                'payment' => ['Pedidos cancelados não podem receber pagamento.'],
            ]);
        }

        if ($pedido->payment_reference && $pedido->payment_url) {
            return $pedido;
        }

        $pedido->loadMissing(['itens', 'usuario']);
        $resultado = $this->gateway->criarCheckout($pedido);

        return DB::transaction(function () use ($pedido, $resultado): Pedido {
            $pedido->update($this->dadosPagamento($resultado));

            return $pedido->fresh(['endereco', 'itens']);
        });
    }

    /** @return array<string, string> */
    private function dadosPagamento(ResultadoCriacaoPagamento $resultado): array
    {
        return [
            'payment_provider' => 'mercado_pago',
            'payment_reference' => $resultado->referencia,
            'payment_url' => $resultado->url,
        ];
    }
}
