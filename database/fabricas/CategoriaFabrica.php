<?php

namespace BancoDeDados\Fabricas;

use App\Modelos\Categoria;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Categoria> */
class CategoriaFabrica extends Factory
{
    protected $model = Categoria::class;

    public function definition(): array
    {
        $nome = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($nome),
            'slug' => Str::slug($nome).'-'.fake()->unique()->numberBetween(10, 99999),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
