<?php

namespace BancoDeDados\Semeadores;

use App\Enumeracoes\PapelUsuario;
use App\Modelos\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemeadorInicial extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        Usuario::query()->updateOrCreate([
            'email' => env('SEED_ADMIN_EMAIL', 'admin@lumora.com.br'),
        ], [
            'name' => 'Administrador Lumora',
            'password' => env('SEED_ADMIN_PASSWORD', 'Admin@123'),
            'role' => PapelUsuario::Administrador,
        ]);

        Usuario::query()->updateOrCreate([
            'email' => env('SEED_CUSTOMER_EMAIL', 'cliente@lumora.com.br'),
        ], [
            'name' => 'Cliente Lumora',
            'password' => env('SEED_CUSTOMER_PASSWORD', 'Cliente@123'),
            'role' => PapelUsuario::Cliente,
        ]);

        $this->call(SemeadorCatalogo::class);
    }
}
