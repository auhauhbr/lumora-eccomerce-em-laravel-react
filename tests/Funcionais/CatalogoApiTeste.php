<?php

namespace Testes\Funcionais;

use App\Modelos\Categoria;
use App\Modelos\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class CatalogoApiTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_catalogo_publico_mostra_somente_produtos_ativos_de_categorias_ativas(): void
    {
        $categoriaAtiva = Categoria::factory()->create();
        $categoriaInativa = Categoria::factory()->inativa()->create();

        $produtoVisivel = Produto::factory()->for($categoriaAtiva, 'categoria')->create();
        Produto::factory()->for($categoriaAtiva, 'categoria')->inativo()->create();
        Produto::factory()->for($categoriaInativa, 'categoria')->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'dados')
            ->assertJsonPath('dados.0.slug', $produtoVisivel->slug);
    }

    public function test_catalogo_filtra_busca_categoria_preco_e_estoque(): void
    {
        $hardware = Categoria::factory()->create(['slug' => 'hardware']);
        $perifericos = Categoria::factory()->create(['slug' => 'perifericos']);

        $notebook = Produto::factory()->for($hardware, 'categoria')->create([
            'name' => 'Notebook Profissional',
            'slug' => 'notebook-profissional',
            'price' => 3500,
            'stock' => 5,
        ]);

        Produto::factory()->for($perifericos, 'categoria')->semEstoque()->create([
            'name' => 'Mouse sem fio',
            'slug' => 'mouse-sem-fio',
            'price' => 120,
        ]);

        $this->getJson('/api/products?search=Notebook&category=hardware&min_price=3000&max_price=4000&in_stock=1')
            ->assertOk()
            ->assertJsonCount(1, 'dados')
            ->assertJsonPath('dados.0.id', $notebook->id);
    }

    public function test_produto_inativo_nao_pode_ser_aberto_publicamente(): void
    {
        $produto = Produto::factory()->inativo()->create();

        $this->getJson("/api/products/{$produto->slug}")
            ->assertNotFound();
    }

    public function test_categoria_publica_lista_apenas_categorias_ativas(): void
    {
        Categoria::factory()->create(['name' => 'Ativa']);
        Categoria::factory()->inativa()->create(['name' => 'Inativa']);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1, 'dados')
            ->assertJsonPath('dados.0.name', 'Ativa');
    }
}
