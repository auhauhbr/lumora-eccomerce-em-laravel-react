<?php

namespace App\Pagamentos;

use Illuminate\Http\Request;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Webhook\WebhookSignatureValidator;

class ValidadorWebhookMercadoPago
{
    public function validar(Request $requisicao): bool
    {
        $segredo = config('services.mercado_pago.webhook_secret');

        // Em desenvolvimento, o segredo só ficará disponível após a aplicação ser liberada.
        if (! is_string($segredo) || $segredo === '') {
            return true;
        }

        try {
            WebhookSignatureValidator::validate(
                $requisicao->header('x-signature'),
                $requisicao->header('x-request-id'),
                $this->obterIdPagamento($requisicao),
                $segredo,
                300,
            );

            return true;
        } catch (InvalidWebhookSignatureException) {
            return false;
        }
    }

    public function obterIdPagamento(Request $requisicao): ?string
    {
        $id = $requisicao->query('data.id')
            ?? $requisicao->input('data.id')
            ?? $requisicao->query('id');

        return is_scalar($id) ? (string) $id : null;
    }
}
