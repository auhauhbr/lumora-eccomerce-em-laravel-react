<?php

namespace App\Http\Controladores\Api;

use App\Http\Controladores\Controlador;
use App\Pagamentos\ValidadorWebhookMercadoPago;
use App\Servicos\ServicoWebhookPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookControlador extends Controlador
{
    public function __construct(
        private readonly ValidadorWebhookMercadoPago $validador,
        private readonly ServicoWebhookPagamento $servicoWebhook,
    ) {}

    public function mercadoPago(Request $requisicao): JsonResponse
    {
        if (! $this->validador->validar($requisicao)) {
            return response()->json(['mensagem' => 'Assinatura inválida.'], 401);
        }

        $referencia = $this->validador->obterIdPagamento($requisicao);
        $payload = $requisicao->all();

        if (! $referencia) {
            Log::info('Webhook do Mercado Pago ignorado por não possuir data.id.', [
                'payload' => $payload,
            ]);

            return response()->json(['mensagem' => 'Evento ignorado.']);
        }

        $evento = $this->servicoWebhook->processar($payload, $referencia);

        return response()->json([
            'mensagem' => 'Evento recebido.',
            'evento_id' => $evento->id,
        ]);
    }
}
