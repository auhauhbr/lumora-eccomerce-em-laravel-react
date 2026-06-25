<?php

namespace BancoDeDados\Semeadores;

use App\Enumeracoes\PapelUsuario;
use App\Modelos\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SemeadorInicial extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Usuario::query()->updateOrCreate([
            'email' => env('SEED_ADMIN_EMAIL', 'admin@lumora.com.br'),
        ], [
            'name' => 'Administrador Lumora',
            'password' => env('SEED_ADMIN_PASSWORD') ?: Str::password(32),
            'role' => PapelUsuario::Administrador,
        ]);

        Usuario::query()->updateOrCreate([
            'email' => env('SEED_CUSTOMER_EMAIL', 'cliente@lumora.com.br'),
        ], [
            'name' => 'Cliente Lumora',
            'password' => env('SEED_CUSTOMER_PASSWORD') ?: Str::password(32),
            'role' => PapelUsuario::Cliente,
        ]);

        $this->call(SemeadorCatalogo::class);
    }
}
