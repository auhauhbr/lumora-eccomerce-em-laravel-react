<?php

namespace Testes\Funcionais;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Modelos\Pedido;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use App\Pagamentos\InterfaceGatewayPagamento;
use App\Pagamentos\ResultadoConsultaPagamento;
use App\Pagamentos\ResultadoCriacaoPagamento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class WebhookPagamentoTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_webhook_aprovado_marca_pedido_como_pago_e_reduz_estoque(): void
    {
        $pedido = $this->criarPedidoComProduto(10, 2);
        $gateway = new GatewayWebhookFalso(
            new ResultadoConsultaPagamento('pagamento-123', $pedido->id, 'approved', '2026-06-22 20:00:00'),
        );
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->postJson('/api/webhooks/mercado-pago?data.id=pagamento-123&type=payment', [
            'id' => 'evento-123',
            'action' => 'payment.updated',
            'type' => 'payment',
            'data' => ['id' => 'pagamento-123'],
        ])->assertOk();

        $pedido->refresh();
        $produto = $pedido->itens()->firstOrFail()->produto()->firstOrFail();

        $this->assertSame(StatusPedido::Pago, $pedido->status);
        $this->assertSame(StatusPagamento::Aprovado, $pedido->payment_status);
        $this->assertSame('pagamento-123', $pedido->payment_reference);
        $this->assertSame(8, $produto->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produto->id,
            'order_id' => $pedido->id,
            'type' => 'out',
            'quantity' => -2,
        ]);
        $this->assertDatabaseHas('payment_events', [
            'order_id' => $pedido->id,
            'external_id' => 'evento-123',
            'event_type' => 'payment.updated',
        ]);
    }

    public function test_webhook_repetido_nao_reduz_estoque_duas_vezes(): void
    {
        $pedido = $this->criarPedidoComProduto(10, 3);
        $gateway = new GatewayWebhookFalso(
            new ResultadoConsultaPagamento('pagamento-duplicado', $pedido->id, 'approved'),
        );
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $payload = [
            'id' => 'evento-duplicado',
            'action' => 'payment.updated',
            'type' => 'payment',
            'data' => ['id' => 'pagamento-duplicado'],
        ];

        $this->postJson('/api/webhooks/mercado-pago?data.id=pagamento-duplicado&type=payment', $payload)
            ->assertOk();
        $this->postJson('/api/webhooks/mercado-pago?data.id=pagamento-duplicado&type=payment', $payload)
            ->assertOk();

        $produto = $pedido->itens()->firstOrFail()->produto()->firstOrFail();

        $this->assertSame(7, $produto->stock);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertDatabaseCount('payment_events', 1);
        $this->assertSame(1, $gateway->consultas);
    }

    public function test_webhook_atualiza_pagamento_rejeitado_sem_reduzir_estoque(): void
    {
        $pedido = $this->criarPedidoComProduto(10, 1);
        $gateway = new GatewayWebhookFalso(
            new ResultadoConsultaPagamento('pagamento-rejeitado', $pedido->id, 'rejected'),
        );
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->postJson('/api/webhooks/mercado-pago?data.id=pagamento-rejeitado&type=payment', [
            'id' => 'evento-rejeitado',
            'action' => 'payment.updated',
            'type' => 'payment',
            'data' => ['id' => 'pagamento-rejeitado'],
        ])->assertOk();

        $pedido->refresh();
        $produto = $pedido->itens()->firstOrFail()->produto()->firstOrFail();

        $this->assertSame(StatusPagamento::Rejeitado, $pedido->payment_status);
        $this->assertSame(StatusPedido::AguardandoPagamento, $pedido->status);
        $this->assertSame(10, $produto->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_evento_desconhecido_e_salvo_e_ignorado_com_seguranca(): void
    {
        $gateway = new GatewayWebhookFalso(
            new ResultadoConsultaPagamento('nao-usado', null, 'pending'),
        );
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->postJson('/api/webhooks/mercado-pago?data.id=evento-externo', [
            'id' => 'evento-desconhecido',
            'action' => 'merchant_order.updated',
            'type' => 'merchant_order',
            'data' => ['id' => 'evento-externo'],
        ])->assertOk();

        $this->assertDatabaseHas('payment_events', [
            'external_id' => 'evento-desconhecido',
            'event_type' => 'merchant_order.updated',
        ]);
        $this->assertSame(0, $gateway->consultas);
    }

    public function test_assinatura_invalida_retorna_nao_autorizado(): void
    {
        config(['services.mercado_pago.webhook_secret' => 'segredo-teste']);

        $this->postJson('/api/webhooks/mercado-pago?data.id=123&type=payment', [
            'id' => 'evento-assinatura',
            'action' => 'payment.updated',
            'type' => 'payment',
            'data' => ['id' => '123'],
        ], [
            'x-signature' => 'ts=123,v1=invalida',
            'x-request-id' => 'req-123',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('payment_events', 0);
    }

    public function test_admin_lista_eventos_e_movimentacoes(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $pedido = $this->criarPedidoComProduto(5, 1);
        $gateway = new GatewayWebhookFalso(
            new ResultadoConsultaPagamento('pagamento-admin', $pedido->id, 'approved'),
        );
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->postJson('/api/webhooks/mercado-pago?data.id=pagamento-admin&type=payment', [
            'id' => 'evento-admin',
            'action' => 'payment.updated',
            'type' => 'payment',
            'data' => ['id' => 'pagamento-admin'],
        ])->assertOk();

        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/admin/payment-events')
            ->assertOk()
            ->assertJsonCount(1, 'dados');

        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/admin/stock-movements')
            ->assertOk()
            ->assertJsonCount(1, 'dados');
    }

    private function criarPedidoComProduto(int $estoque, int $quantidade): Pedido
    {
        $cliente = Usuario::factory()->create();
        $endereco = $cliente->enderecos()->create([
            'zip_code' => '01001000',
            'street' => 'Praça da Sé',
            'number' => '100',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
        $produto = Produto::factory()->create([
            'name' => 'Produto do webhook',
            'price' => 100,
            'stock' => $estoque,
        ]);
        $pedido = $cliente->pedidos()->create([
            'address_id' => $endereco->id,
            'status' => StatusPedido::AguardandoPagamento,
            'payment_status' => StatusPagamento::Pendente,
            'payment_provider' => 'mercado_pago',
            'payment_reference' => 'preferencia-original',
            'payment_url' => 'https://sandbox.mercadopago.com/preferencia',
            'subtotal' => 100 * $quantidade,
            'shipping_value' => 0,
            'total' => 100 * $quantidade,
        ]);
        $pedido->itens()->create([
            'product_id' => $produto->id,
            'product_name' => $produto->name,
            'unit_price' => 100,
            'quantity' => $quantidade,
            'total' => 100 * $quantidade,
        ]);

        return $pedido;
    }
}

class GatewayWebhookFalso implements InterfaceGatewayPagamento
{
    public int $consultas = 0;

    public function __construct(
        private readonly ResultadoConsultaPagamento $resultado,
    ) {}

    public function criarCheckout(Pedido $pedido): ResultadoCriacaoPagamento
    {
        return new ResultadoCriacaoPagamento('preferencia', 'https://checkout.teste');
    }

    public function consultarPagamento(string $referencia): ResultadoConsultaPagamento
    {
        $this->consultas++;

        return $this->resultado;
    }
}
