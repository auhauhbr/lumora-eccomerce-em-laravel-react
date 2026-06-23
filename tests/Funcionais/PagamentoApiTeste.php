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

class PagamentoApiTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_cliente_inicia_pagamento_e_checkout_e_salvo_no_pedido(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = $this->criarPedido($cliente);
        $gateway = new GatewayPagamentoFalso;
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedido->id}/payment")
            ->assertOk()
            ->assertJsonPath('dados.order_id', $pedido->id)
            ->assertJsonPath('dados.payment_provider', 'mercado_pago')
            ->assertJsonPath('dados.payment_reference', 'preferencia-teste')
            ->assertJsonPath('dados.payment_url', 'https://sandbox.mercadopago.com/checkout/teste')
            ->assertJsonPath('dados.payment_status', StatusPagamento::Pendente->value);

        $this->assertDatabaseHas('orders', [
            'id' => $pedido->id,
            'payment_provider' => 'mercado_pago',
            'payment_reference' => 'preferencia-teste',
            'payment_url' => 'https://sandbox.mercadopago.com/checkout/teste',
            'status' => StatusPedido::AguardandoPagamento->value,
            'payment_status' => StatusPagamento::Pendente->value,
        ]);
        $this->assertSame(1, $gateway->quantidadeChamadas);
    }

    public function test_endpoint_e_idempotente_quando_pedido_ja_tem_checkout(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = $this->criarPedido($cliente);
        $gateway = new GatewayPagamentoFalso;
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedido->id}/payment")
            ->assertOk();

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedido->id}/payment")
            ->assertOk()
            ->assertJsonPath('dados.payment_reference', 'preferencia-teste');

        $this->assertSame(1, $gateway->quantidadeChamadas);
    }

    public function test_cliente_nao_inicia_pagamento_de_pedido_alheio(): void
    {
        $dono = Usuario::factory()->create();
        $intruso = Usuario::factory()->create();
        $pedido = $this->criarPedido($dono);
        $this->app->instance(InterfaceGatewayPagamento::class, new GatewayPagamentoFalso);

        $this->actingAs($intruso, 'sanctum')
            ->postJson("/api/orders/{$pedido->id}/payment")
            ->assertNotFound();
    }

    public function test_pedido_pago_ou_cancelado_nao_cria_novo_checkout(): void
    {
        $cliente = Usuario::factory()->create();
        $gateway = new GatewayPagamentoFalso;
        $this->app->instance(InterfaceGatewayPagamento::class, $gateway);

        $pedidoPago = $this->criarPedido($cliente);
        $pedidoPago->update([
            'status' => StatusPedido::Pago,
            'payment_status' => StatusPagamento::Aprovado,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedidoPago->id}/payment")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment');

        $pedidoCancelado = $this->criarPedido($cliente);
        $pedidoCancelado->update([
            'status' => StatusPedido::Cancelado,
            'payment_status' => StatusPagamento::Cancelado,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedidoCancelado->id}/payment")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('payment');

        $this->assertSame(0, $gateway->quantidadeChamadas);
    }

    public function test_cliente_consulta_status_do_pagamento_do_proprio_pedido(): void
    {
        $cliente = Usuario::factory()->create();
        $pedido = $this->criarPedido($cliente);

        $this->actingAs($cliente, 'sanctum')
            ->getJson("/api/orders/{$pedido->id}/payment-status")
            ->assertOk()
            ->assertJsonPath('dados.order_status', StatusPedido::AguardandoPagamento->value)
            ->assertJsonPath('dados.payment_status', StatusPagamento::Pendente->value);
    }

    private function criarPedido(Usuario $cliente): Pedido
    {
        $endereco = $cliente->enderecos()->create([
            'zip_code' => '01001000',
            'street' => 'Praça da Sé',
            'number' => '100',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
        $produto = Produto::factory()->create([
            'name' => 'Produto para pagamento',
            'price' => 250,
            'stock' => 10,
        ]);

        $pedido = $cliente->pedidos()->create([
            'address_id' => $endereco->id,
            'status' => StatusPedido::AguardandoPagamento,
            'payment_status' => StatusPagamento::Pendente,
            'subtotal' => 250,
            'shipping_value' => 0,
            'total' => 250,
        ]);
        $pedido->itens()->create([
            'product_id' => $produto->id,
            'product_name' => $produto->name,
            'unit_price' => 250,
            'quantity' => 1,
            'total' => 250,
        ]);

        return $pedido;
    }
}

class GatewayPagamentoFalso implements InterfaceGatewayPagamento
{
    public int $quantidadeChamadas = 0;

    public function criarCheckout(Pedido $pedido): ResultadoCriacaoPagamento
    {
        $this->quantidadeChamadas++;

        return new ResultadoCriacaoPagamento(
            'preferencia-teste',
            'https://sandbox.mercadopago.com/checkout/teste',
        );
    }

    public function consultarPagamento(string $referencia): ResultadoConsultaPagamento
    {
        return new ResultadoConsultaPagamento($referencia, null, 'pending');
    }
}
