<?php

namespace Database\Seeders;

use BancoDeDados\Semeadores\SemeadorInicial;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SemeadorInicial::class);
    }
}
