<?php

namespace App\Provedores;

use App\Pagamentos\GatewayMercadoPago;
use App\Pagamentos\InterfaceGatewayPagamento;
use Illuminate\Support\ServiceProvider;

class ProvedorAplicacao extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            InterfaceGatewayPagamento::class,
            GatewayMercadoPago::class,
        );
    }

    public function boot(): void
    {
        //
    }
}
