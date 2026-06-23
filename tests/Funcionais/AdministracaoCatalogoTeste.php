<?php

namespace Testes\Funcionais;

use App\Modelos\Categoria;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class AdministracaoCatalogoTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_administrador_cria_categoria_e_produto(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $categoria = $this->actingAs($administrador, 'sanctum')
            ->postJson('/api/admin/categories', [
                'name' => 'Informática',
                'description' => 'Equipamentos de informática.',
            ])
            ->assertCreated()
            ->assertJsonPath('dados.slug', 'informatica')
            ->json('dados');

        $this->actingAs($administrador, 'sanctum')
            ->postJson('/api/admin/products', [
                'category_id' => $categoria['id'],
                'name' => 'Notebook Lumora Pro',
                'description' => 'Notebook para produtividade.',
                'price' => 4999.90,
                'stock' => 10,
            ])
            ->assertCreated()
            ->assertJsonPath('dados.slug', 'notebook-lumora-pro');
    }

    public function test_cliente_nao_cria_produto(): void
    {
        $cliente = Usuario::factory()->create();
        $categoria = Categoria::factory()->create();

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/admin/products', [
                'category_id' => $categoria->id,
                'name' => 'Produto bloqueado',
                'price' => 100,
                'stock' => 1,
            ])
            ->assertForbidden();
    }

    public function test_preco_precisa_ser_positivo_e_estoque_nao_pode_ser_negativo(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $categoria = Categoria::factory()->create();

        $this->actingAs($administrador, 'sanctum')
            ->postJson('/api/admin/products', [
                'category_id' => $categoria->id,
                'name' => 'Produto inválido',
                'price' => 0,
                'stock' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price', 'stock']);
    }

    public function test_categoria_com_produtos_nao_pode_ser_excluida(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $categoria = Categoria::factory()->create();
        Produto::factory()->for($categoria, 'categoria')->create();

        $this->actingAs($administrador, 'sanctum')
            ->deleteJson("/api/admin/categories/{$categoria->slug}")
            ->assertUnprocessable();
    }

    public function test_administrador_pode_desativar_produto(): void
    {
        $administrador = Usuario::factory()->administrador()->create();
        $produto = Produto::factory()->create();

        $this->actingAs($administrador, 'sanctum')
            ->patchJson("/api/admin/products/{$produto->slug}/toggle-active")
            ->assertOk()
            ->assertJsonPath('dados.is_active', false);

        $this->getJson("/api/products/{$produto->slug}")
            ->assertNotFound();
    }
}
