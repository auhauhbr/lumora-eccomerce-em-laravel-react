<?php

namespace BancoDeDados\Fabricas;

use App\Enumeracoes\PapelUsuario;
use App\Modelos\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<Usuario> */
class UsuarioFabrica extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $senha;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$senha ??= Hash::make('senha123'),
            'role' => PapelUsuario::Cliente,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => [
            'role' => PapelUsuario::Administrador,
        ]);
    }
}
