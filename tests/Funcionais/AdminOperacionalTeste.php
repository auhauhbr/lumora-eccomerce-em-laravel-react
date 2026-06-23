<?php

namespace Testes\Funcionais;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Modelos\EventoPagamento;
use App\Modelos\Pedido;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use App\Servicos\ServicoEstoque;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class AdminOperacionalTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_admin_ajusta_estoque_e_registra_movimentacao(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $produto = Produto::factory()->create(['stock' => 10]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/products/{$produto->slug}/stock-adjustment", [
                'quantity' => 5,
                'reason' => 'Entrada por inventário',
            ])
            ->assertOk()
            ->assertJsonPath('dados.stock', 15);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produto->id,
            'type' => 'adjustment',
            'quantity' => 5,
            'reason' => 'Entrada por inventário',
        ]);
    }

    public function test_ajuste_nao_pode_deixar_estoque_negativo(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $produto = Produto::factory()->create(['stock' => 3]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/admin/products/{$produto->slug}/stock-adjustment", [
                'quantity' => -4,
                'reason' => 'Correção de inventário',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertSame(3, $produto->fresh()->stock);
    }

    public function test_cliente_nao_ajusta_estoque(): void
    {
        $cliente = Usuario::factory()->create();
        $produto = Produto::factory()->create(['stock' => 10]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/admin/products/{$produto->slug}/stock-adjustment", [
                'quantity' => 1,
                'reason' => 'Tentativa indevida',
            ])
            ->assertForbidden();
    }

    public function test_admin_avanca_status_operacional_na_ordem_correta(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $pedido = $this->criarPedidoPago();

        foreach ([
            StatusPedido::EmProcessamento,
            StatusPedido::Enviado,
            StatusPedido::Entregue,
        ] as $status) {
            $this->actingAs($admin, 'sanctum')
                ->patchJson("/api/admin/orders/{$pedido->id}/status", [
                    'status' => $status->value,
                ])
                ->assertOk()
                ->assertJsonPath('dados.status', $status->value);
        }
    }

    public function test_transicao_invalida_de_status_e_bloqueada(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $pedido = $this->criarPedidoPago();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$pedido->id}/status", [
                'status' => StatusPedido::Entregue->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_cancelamento_de_pedido_pago_devolve_estoque_uma_unica_vez(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $pedido = $this->criarPedidoPago(10, 2);
        $produto = $pedido->itens()->firstOrFail()->produto()->firstOrFail();

        app(ServicoEstoque::class)->reduzirParaPedido($pedido);
        $this->assertSame(8, $produto->fresh()->stock);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/orders/{$pedido->id}/status", [
                'status' => StatusPedido::Cancelado->value,
            ])
            ->assertOk()
            ->assertJsonPath('dados.status', StatusPedido::Cancelado->value)
            ->assertJsonPath('dados.payment_status', StatusPagamento::Reembolsado->value);

        $this->assertSame(10, $produto->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $produto->id,
            'order_id' => $pedido->id,
            'type' => 'cancellation',
            'quantity' => 2,
        ]);

        app(ServicoEstoque::class)->restaurarParaPedido($pedido);
        $this->assertSame(10, $produto->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_dashboard_retorna_metricas_operacionais(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $pedidoPago = $this->criarPedidoPago(10, 1, 350);
        $this->criarPedidoPendente();
        Produto::factory()->create(['stock' => 2, 'is_active' => true]);
        Produto::factory()->create(['stock' => 20, 'is_active' => true]);
        EventoPagamento::create([
            'order_id' => $pedidoPago->id,
            'provider' => 'mercado_pago',
            'event_type' => 'payment.updated',
            'external_id' => 'dashboard-evento',
            'payload' => ['type' => 'payment'],
            'processed_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('dados.total_vendas', 1)
            ->assertJsonPath('dados.faturamento_total', '350.00')
            ->assertJsonPath('dados.faturamento_mes', '350.00')
            ->assertJsonPath('dados.pedidos_aguardando_pagamento', 1)
            ->assertJsonPath('dados.pedidos_pagos', 1)
            ->assertJsonPath('dados.produtos_estoque_baixo', 1)
            ->assertJsonCount(2, 'dados.ultimos_pedidos')
            ->assertJsonCount(1, 'dados.ultimos_eventos_pagamento');
    }

    private function criarPedidoPago(
        int $estoque = 10,
        int $quantidade = 1,
        float $preco = 100,
    ): Pedido {
        return $this->criarPedido(
            StatusPedido::Pago,
            StatusPagamento::Aprovado,
            $estoque,
            $quantidade,
            $preco,
        );
    }

    private function criarPedidoPendente(): Pedido
    {
        return $this->criarPedido(
            StatusPedido::AguardandoPagamento,
            StatusPagamento::Pendente,
            10,
            1,
            50,
        );
    }

    private function criarPedido(
        StatusPedido $status,
        StatusPagamento $statusPagamento,
        int $estoque,
        int $quantidade,
        float $preco,
    ): Pedido {
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
            'stock' => $estoque,
            'price' => $preco,
        ]);
        $pedido = $cliente->pedidos()->create([
            'address_id' => $endereco->id,
            'status' => $status,
            'payment_status' => $statusPagamento,
            'subtotal' => $preco * $quantidade,
            'shipping_value' => 0,
            'total' => $preco * $quantidade,
            'paid_at' => $statusPagamento === StatusPagamento::Aprovado ? now() : null,
        ]);
        $pedido->itens()->create([
            'product_id' => $produto->id,
            'product_name' => $produto->name,
            'unit_price' => $preco,
            'quantity' => $quantidade,
            'total' => $preco * $quantidade,
        ]);

        return $pedido;
    }
}
