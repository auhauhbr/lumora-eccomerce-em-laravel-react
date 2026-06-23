<?php

namespace Testes\Funcionais;

use App\Modelos\Categoria;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class CarrinhoApiTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_rota_do_carrinho_exige_autenticacao(): void
    {
        $this->getJson('/api/cart')->assertUnauthorized();
    }

    public function test_cliente_adiciona_produto_e_recebe_subtotal(): void
    {
        $cliente = Usuario::factory()->create();
        $produto = Produto::factory()->create([
            'price' => 199.90,
            'stock' => 10,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 2,
            ])
            ->assertCreated()
            ->assertJsonPath('dados.itens.0.product_id', $produto->id)
            ->assertJsonPath('dados.itens.0.quantity', 2)
            ->assertJsonPath('dados.itens.0.unit_price', '199.90')
            ->assertJsonPath('subtotal', '399.80')
            ->assertJsonPath('quantidade_itens', 2);
    }

    public function test_produto_repetido_soma_quantidade_sem_duplicar_item(): void
    {
        $cliente = Usuario::factory()->create();
        $produto = Produto::factory()->create([
            'price' => 50,
            'stock' => 10,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 2,
            ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 3,
            ])
            ->assertCreated()
            ->assertJsonCount(1, 'dados.itens')
            ->assertJsonPath('dados.itens.0.quantity', 5)
            ->assertJsonPath('subtotal', '250.00');
    }

    public function test_cliente_atualiza_quantidade_remove_item_e_limpa_carrinho(): void
    {
        $cliente = Usuario::factory()->create();
        $produtoUm = Produto::factory()->create(['stock' => 10]);
        $produtoDois = Produto::factory()->create(['stock' => 10]);

        $primeiroItem = $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produtoUm->id,
                'quantity' => 1,
            ])
            ->json('dados.itens.0');

        $this->actingAs($cliente, 'sanctum')
            ->patchJson("/api/cart/items/{$primeiroItem['id']}", [
                'quantity' => 4,
            ])
            ->assertOk()
            ->assertJsonPath('dados.itens.0.quantity', 4);

        $segundoItem = $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produtoDois->id,
                'quantity' => 1,
            ])
            ->json('dados.itens.1');

        $this->actingAs($cliente, 'sanctum')
            ->deleteJson("/api/cart/items/{$segundoItem['id']}")
            ->assertOk()
            ->assertJsonCount(1, 'dados.itens');

        $this->actingAs($cliente, 'sanctum')
            ->deleteJson('/api/cart')
            ->assertOk()
            ->assertJsonCount(0, 'dados.itens')
            ->assertJsonPath('subtotal', '0.00');
    }

    public function test_carrinho_bloqueia_quantidade_acima_do_estoque(): void
    {
        $cliente = Usuario::factory()->create();
        $produto = Produto::factory()->create(['stock' => 3]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_carrinho_bloqueia_produto_ou_categoria_inativa(): void
    {
        $cliente = Usuario::factory()->create();
        $produtoInativo = Produto::factory()->inativo()->create();
        $categoriaInativa = Categoria::factory()->inativa()->create();
        $produtoDeCategoriaInativa = Produto::factory()
            ->for($categoriaInativa, 'categoria')
            ->create();

        foreach ([$produtoInativo, $produtoDeCategoriaInativa] as $produto) {
            $this->actingAs($cliente, 'sanctum')
                ->postJson('/api/cart/items', [
                    'product_id' => $produto->id,
                    'quantity' => 1,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('product_id');
        }
    }

    public function test_cliente_nao_altera_item_do_carrinho_de_outro_cliente(): void
    {
        $dono = Usuario::factory()->create();
        $intruso = Usuario::factory()->create();
        $produto = Produto::factory()->create(['stock' => 10]);

        $item = $this->actingAs($dono, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 1,
            ])
            ->json('dados.itens.0');

        $this->actingAs($intruso, 'sanctum')
            ->patchJson("/api/cart/items/{$item['id']}", [
                'quantity' => 2,
            ])
            ->assertNotFound();
    }

    public function test_preco_do_item_e_preservado_apos_mudanca_no_produto(): void
    {
        $cliente = Usuario::factory()->create();
        $produto = Produto::factory()->create([
            'price' => 100,
            'stock' => 10,
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/cart/items', [
                'product_id' => $produto->id,
                'quantity' => 2,
            ]);

        $produto->update(['price' => 150]);

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('dados.itens.0.unit_price', '100.00')
            ->assertJsonPath('subtotal', '200.00');
    }
}
