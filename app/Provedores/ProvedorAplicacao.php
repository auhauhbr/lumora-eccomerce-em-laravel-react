<?php

namespace App\Provedores;

use App\Pagamentos\GatewayMercadoPago;
use App\Pagamentos\InterfaceGatewayPagamento;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('autenticacao', function (Request $requisicao): Limit {
            return Limit::perMinute(5)->by(
                $requisicao->ip().'|'.(string) $requisicao->input('email'),
            );
        });

        RateLimiter::for('api-publica', function (Request $requisicao): Limit {
            return Limit::perMinute(120)->by($requisicao->ip());
        });

        RateLimiter::for('webhook-pagamento', function (Request $requisicao): Limit {
            return Limit::perMinute(60)->by($requisicao->ip());
        });
    }
}
