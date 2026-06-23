<?php

namespace Testes\Funcionais;

use App\Modelos\Categoria;
use App\Modelos\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class GraphQLCatalogoTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_graphql_lista_produtos_ativos_com_categoria(): void
    {
        $categoria = Categoria::factory()->create([
            'name' => 'Hardware',
            'slug' => 'hardware',
        ]);

        $produto = Produto::factory()->for($categoria, 'categoria')->create([
            'name' => 'Notebook Lumora',
            'slug' => 'notebook-lumora',
            'stock' => 5,
        ]);

        Produto::factory()->for($categoria, 'categoria')->inativo()->create();

        $resposta = $this->postGraphQL(<<<'GRAPHQL'
            query {
                products {
                    id
                    name
                    slug
                    available
                    category {
                        name
                        slug
                    }
                }
            }
            GRAPHQL);

        $resposta
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.id', (string) $produto->id)
            ->assertJsonPath('data.products.0.available', true)
            ->assertJsonPath('data.products.0.category.slug', 'hardware');
    }

    public function test_graphql_busca_produto_por_slug(): void
    {
        $produto = Produto::factory()->create([
            'slug' => 'monitor-ultrawide',
        ]);

        $this->postGraphQL(<<<'GRAPHQL'
            query Produto($slug: String!) {
                product(slug: $slug) {
                    id
                    slug
                    price
                    stock
                }
            }
            GRAPHQL, ['slug' => $produto->slug])
            ->assertJsonPath('data.product.id', (string) $produto->id)
            ->assertJsonPath('data.product.slug', 'monitor-ultrawide');
    }

    public function test_graphql_lista_categorias_ativas_e_produtos_ativos(): void
    {
        $categoriaAtiva = Categoria::factory()->create([
            'name' => 'Periféricos',
        ]);
        Categoria::factory()->inativa()->create();

        $produtoAtivo = Produto::factory()->for($categoriaAtiva, 'categoria')->create();
        Produto::factory()->for($categoriaAtiva, 'categoria')->inativo()->create();

        $this->postGraphQL(<<<'GRAPHQL'
            query {
                categories {
                    id
                    name
                    products {
                        id
                    }
                }
            }
            GRAPHQL)
            ->assertJsonCount(1, 'data.categories')
            ->assertJsonCount(1, 'data.categories.0.products')
            ->assertJsonPath('data.categories.0.products.0.id', (string) $produtoAtivo->id);
    }

    public function test_graphql_aplica_filtros_do_catalogo(): void
    {
        $hardware = Categoria::factory()->create(['slug' => 'hardware']);
        $perifericos = Categoria::factory()->create(['slug' => 'perifericos']);

        $produtoEsperado = Produto::factory()->for($hardware, 'categoria')->create([
            'name' => 'Notebook Profissional',
            'price' => 3500,
            'stock' => 3,
        ]);

        Produto::factory()->for($perifericos, 'categoria')->semEstoque()->create([
            'name' => 'Mouse sem fio',
            'price' => 120,
        ]);

        $this->postGraphQL(<<<'GRAPHQL'
            query {
                products(
                    search: "Notebook"
                    category: "hardware"
                    min_price: 3000
                    max_price: 4000
                    in_stock: true
                ) {
                    id
                    name
                }
            }
            GRAPHQL)
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.id', (string) $produtoEsperado->id);
    }

    public function test_graphql_nao_retorna_produto_inativo_por_slug(): void
    {
        $produto = Produto::factory()->inativo()->create();

        $this->postGraphQL(<<<'GRAPHQL'
            query Produto($slug: String!) {
                product(slug: $slug) {
                    id
                }
            }
            GRAPHQL, ['slug' => $produto->slug])
            ->assertJsonPath('data.product', null);
    }

    /** @param array<string, mixed> $variaveis */
    private function postGraphQL(string $consulta, array $variaveis = [])
    {
        return $this->postJson('/graphql', [
            'query' => $consulta,
            'variables' => $variaveis,
        ]);
    }
}
