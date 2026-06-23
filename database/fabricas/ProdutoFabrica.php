<?php

namespace BancoDeDados\Fabricas;

use App\Modelos\Categoria;
use App\Modelos\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Produto> */
class ProdutoFabrica extends Factory
{
    protected $model = Produto::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(3, true);

        return [
            'category_id' => Categoria::factory(),
            'name' => Str::title($nome),
            'slug' => Str::slug($nome).'-'.fake()->unique()->numberBetween(10, 99999),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 10, 5000),
            'stock' => fake()->numberBetween(0, 100),
            'image_url' => 'https://picsum.photos/seed/'.Str::slug($nome).'/800/600',
            'is_active' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function semEstoque(): static
    {
        return $this->state(fn () => [
            'stock' => 0,
        ]);
    }
}
