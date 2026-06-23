<?php

namespace Testes\Funcionais;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class PedidoApiTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_cliente_cria_pedido_a_partir_do_carrinho(): void
    {
        $cliente = Usuario::factory()->create();
        $endereco = $cliente->enderecos()->create($this->dadosEndereco());
        $produto = Produto::factory()->create([
            'name' => 'Teclado Mecânico',
            'price' => 199.90,
            'stock' => 10,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 2,
            ]);

        $resposta = $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', [
                'address_id' => $endereco->id,
            ])
            ->assertCreated()
            ->assertJsonPath('dados.status', StatusPedido::AguardandoPagamento->value)
            ->assertJsonPath('dados.payment_status', StatusPagamento::Pendente->value)
            ->assertJsonPath('dados.subtotal', '399.80')
            ->assertJsonPath('dados.total', '399.80')
            ->assertJsonPath('dados.itens.0.product_name', 'Teclado Mecânico')
            ->assertJsonPath('dados.itens.0.unit_price', '199.90')
            ->assertJsonPath('dados.itens.0.quantity', 2);

        $pedidoId = $resposta->json('dados.id');

        $this->assertDatabaseHas('orders', [
            'id' => $pedidoId,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('products', [
            'id' => $produto->id,
            'stock' => 10,
        ]);
    }

    public function test_pedido_preserva_nome_e_preco_do_momento_da_compra(): void
    {
        $cliente = Usuario::factory()->create();
        $endereco = $cliente->enderecos()->create($this->dadosEndereco());
        $produto = Produto::factory()->create([
            'name' => 'Monitor Original',
            'price' => 1000,
            'stock' => 5,
        ]);

        $this->actingAs($cliente, 'sanctum')->postJson('/api/cart/items', [
            'product_id' => $produto->id,
            'quantity' => 1,
        ]);

        $pedido = $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', ['address_id' => $endereco->id])
            ->json('dados');

        $produto->update([
            'name' => 'Monitor Atualizado',
            'price' => 1500,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->getJson("/api/orders/{$pedido['id']}")
            ->assertOk()
            ->assertJsonPath('dados.itens.0.product_name', 'Monitor Original')
            ->assertJsonPath('dados.itens.0.unit_price', '1000.00');
    }

    public function test_pedido_bloqueia_carrinho_vazio_estoque_insuficiente_e_endereco_alheio(): void
    {
        $cliente = Usuario::factory()->create();
        $outroCliente = Usuario::factory()->create();
        $endereco = $cliente->enderecos()->create($this->dadosEndereco());
        $enderecoAlheio = $outroCliente->enderecos()->create($this->dadosEndereco('20040002'));

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', ['address_id' => $endereco->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $produto = Produto::factory()->create(['stock' => 2]);
        $this->actingAs($cliente, 'sanctum')->postJson('/api/cart/items', [
            'product_id' => $produto->id,
            'quantity' => 2,
        ]);
        $produto->update(['stock' => 1]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', ['address_id' => $endereco->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cart');

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', ['address_id' => $enderecoAlheio->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_id');
    }

    public function test_cliente_ve_somente_seus_pedidos_e_pode_cancelar_pendente(): void
    {
        $cliente = Usuario::factory()->create();
        $outroCliente = Usuario::factory()->create();
        $pedido = $this->criarPedido($cliente);
        $pedidoAlheio = $this->criarPedido($outroCliente);

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonCount(1, 'dados')
            ->assertJsonPath('dados.0.id', $pedido['id']);

        $this->actingAs($cliente, 'sanctum')
            ->getJson("/api/orders/{$pedidoAlheio['id']}")
            ->assertNotFound();

        $this->actingAs($cliente, 'sanctum')
            ->postJson("/api/orders/{$pedido['id']}/cancel")
            ->assertOk()
            ->assertJsonPath('dados.status', StatusPedido::Cancelado->value)
            ->assertJsonPath('dados.payment_status', StatusPagamento::Cancelado->value);
    }

    public function test_administrador_ve_todos_os_pedidos(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $this->criarPedido(Usuario::factory()->create());
        $this->criarPedido(Usuario::factory()->create());

        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/admin/orders')
            ->assertOk()
            ->assertJsonCount(2, 'dados');
    }

    /** @return array<string, mixed> */
    private function criarPedido(Usuario $cliente): array
    {
        $endereco = $cliente->enderecos()->create($this->dadosEndereco());
        $produto = Produto::factory()->create(['stock' => 10]);

        $this->actingAs($cliente, 'sanctum')->postJson('/api/cart/items', [
            'product_id' => $produto->id,
            'quantity' => 1,
        ]);

        return $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/orders', ['address_id' => $endereco->id])
            ->assertCreated()
            ->json('dados');
    }

    /** @return array<string, string> */
    private function dadosEndereco(string $cep = '01001000'): array
    {
        return [
            'zip_code' => $cep,
            'street' => 'Praça da Sé',
            'number' => '100',
            'complement' => 'Conjunto 10',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }
}
