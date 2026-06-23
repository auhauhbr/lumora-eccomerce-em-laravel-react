<?php

namespace App\Pagamentos;

use App\Modelos\ItemPedido;
use App\Modelos\Pedido;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPDefaultHttpClient;

class GatewayMercadoPago implements InterfaceGatewayPagamento
{
    public function criarCheckout(Pedido $pedido): ResultadoCriacaoPagamento
    {
        $this->configurarSdk();

        $opcoes = new RequestOptions;
        $opcoes->setCustomHeaders([
            'X-Idempotency-Key: lumora-order-'.$pedido->id,
        ]);

        try {
            $preferencia = (new PreferenceClient)->create(
                $this->montarPreferencia($pedido),
                $opcoes,
            );
        } catch (MPApiException $excecao) {
            Log::error('Mercado Pago recusou a criação da preferência.', [
                'pedido_id' => $pedido->id,
                'status_http' => $excecao->getApiResponse()->getStatusCode(),
                'resposta' => $excecao->getApiResponse()->getContent(),
            ]);

            throw ValidationException::withMessages([
                'payment' => ['O Mercado Pago não conseguiu iniciar o pagamento.'],
            ]);
        } catch (\Throwable $excecao) {
            report($excecao);

            throw ValidationException::withMessages([
                'payment' => ['Não foi possível conectar ao Mercado Pago agora.'],
            ]);
        }

        $url = config('services.mercado_pago.sandbox')
            ? $preferencia->sandbox_init_point
            : $preferencia->init_point;

        if (! $preferencia->id || ! $url) {
            throw ValidationException::withMessages([
                'payment' => ['O Mercado Pago retornou um checkout incompleto.'],
            ]);
        }

        return new ResultadoCriacaoPagamento($preferencia->id, $url);
    }

    public function consultarPagamento(string $referencia): ResultadoConsultaPagamento
    {
        $this->configurarSdk();

        try {
            $pagamento = (new PaymentClient)->get((int) $referencia);
        } catch (\Throwable $excecao) {
            report($excecao);

            throw ValidationException::withMessages([
                'payment' => ['Não foi possível consultar o pagamento no Mercado Pago.'],
            ]);
        }

        return new ResultadoConsultaPagamento(
            (string) $pagamento->id,
            is_numeric($pagamento->external_reference) ? (int) $pagamento->external_reference : null,
            (string) $pagamento->status,
            $pagamento->date_approved,
        );
    }

    /** @return array<string, mixed> */
    private function montarPreferencia(Pedido $pedido): array
    {
        $pedido->loadMissing(['itens', 'usuario']);
        $frontend = rtrim(config('services.mercado_pago.frontend_url'), '/');
        $aplicacao = rtrim(config('app.url'), '/');

        return [
            'items' => $pedido->itens
                ->map(fn (ItemPedido $item) => [
                    'id' => (string) $item->product_id,
                    'title' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'currency_id' => 'BRL',
                ])
                ->values()
                ->all(),
            'payer' => [
                'name' => $pedido->usuario->name,
                'email' => $pedido->usuario->email,
            ],
            'external_reference' => (string) $pedido->id,
            'metadata' => [
                'order_id' => $pedido->id,
            ],
            'back_urls' => [
                'success' => "{$frontend}/pagamento/sucesso?pedido={$pedido->id}",
                'pending' => "{$frontend}/pagamento/pendente?pedido={$pedido->id}",
                'failure' => "{$frontend}/pagamento/falha?pedido={$pedido->id}",
            ],
            'notification_url' => "{$aplicacao}/api/webhooks/mercado-pago",
            'statement_descriptor' => 'LUMORA',
            'auto_return' => 'approved',
            'shipments' => [
                'cost' => (float) $pedido->shipping_value,
                'mode' => 'not_specified',
            ],
        ];
    }

    private function configurarSdk(): void
    {
        $token = config('services.mercado_pago.access_token');

        if (! is_string($token) || $token === '') {
            throw ValidationException::withMessages([
                'payment' => ['Configure MERCADO_PAGO_ACCESS_TOKEN no arquivo .env.'],
            ]);
        }

        MercadoPagoConfig::setAccessToken($token);
        MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::SERVER);
        MercadoPagoConfig::setMaxRetries(2);
        MercadoPagoConfig::setConnectionTimeout(10000);
        MercadoPagoConfig::setHttpClient(new MPDefaultHttpClient(
            new RequisicaoCurlComCertificado(config('services.mercado_pago.ca_bundle')),
        ));
    }
}
