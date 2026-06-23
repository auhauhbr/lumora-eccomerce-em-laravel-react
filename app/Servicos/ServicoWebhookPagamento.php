<?php

namespace App\Servicos;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Modelos\EventoPagamento;
use App\Modelos\Pedido;
use App\Pagamentos\InterfaceGatewayPagamento;
use App\Pagamentos\ResultadoConsultaPagamento;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServicoWebhookPagamento
{
    public function __construct(
        private readonly InterfaceGatewayPagamento $gateway,
        private readonly ServicoEstoque $servicoEstoque,
    ) {}

    /** @param array<string, mixed> $payload */
    public function processar(array $payload, string $referenciaPagamento): EventoPagamento
    {
        $tipo = (string) ($payload['type'] ?? 'unknown');
        $acao = (string) ($payload['action'] ?? $tipo);
        $idEvento = (string) ($payload['id'] ?? "{$acao}:{$referenciaPagamento}");

        $evento = EventoPagamento::query()->firstOrCreate([
            'provider' => 'mercado_pago',
            'event_type' => $acao,
            'external_id' => $idEvento,
        ], [
            'payload' => $payload,
        ]);

        if ($evento->processed_at) {
            return $evento;
        }

        if ($tipo !== 'payment') {
            $evento->update(['processed_at' => now()]);

            return $evento->fresh();
        }

        $pagamento = $this->gateway->consultarPagamento($referenciaPagamento);

        return DB::transaction(function () use ($evento, $pagamento): EventoPagamento {
            $pedido = $this->localizarPedido($pagamento);

            if (! $pedido) {
                Log::warning('Webhook do Mercado Pago sem pedido correspondente.', [
                    'pagamento_id' => $pagamento->referencia,
                    'pedido_externo' => $pagamento->pedidoId,
                ]);

                $evento->update(['processed_at' => now()]);

                return $evento->fresh();
            }

            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            $evento->update(['order_id' => $pedido->id]);
            $this->aplicarStatus($pedido, $pagamento);
            $evento->update(['processed_at' => now()]);

            return $evento->fresh();
        });
    }

    private function localizarPedido(ResultadoConsultaPagamento $pagamento): ?Pedido
    {
        if ($pagamento->pedidoId) {
            return Pedido::query()->find($pagamento->pedidoId);
        }

        return Pedido::query()
            ->where('payment_reference', $pagamento->referencia)
            ->first();
    }

    private function aplicarStatus(Pedido $pedido, ResultadoConsultaPagamento $pagamento): void
    {
        $pedido->payment_reference = $pagamento->referencia;

        match ($pagamento->status) {
            'approved' => $this->aprovar($pedido, $pagamento),
            'rejected' => $this->atualizar($pedido, StatusPagamento::Rejeitado),
            'cancelled' => $this->atualizar($pedido, StatusPagamento::Cancelado),
            'refunded', 'charged_back' => $this->atualizar($pedido, StatusPagamento::Reembolsado),
            'expired' => $this->expirar($pedido),
            default => $this->atualizar($pedido, StatusPagamento::Pendente),
        };
    }

    private function aprovar(Pedido $pedido, ResultadoConsultaPagamento $pagamento): void
    {
        if ($pedido->payment_status !== StatusPagamento::Aprovado) {
            $this->servicoEstoque->reduzirParaPedido($pedido);
        }

        $pedido->status = StatusPedido::Pago;
        $pedido->payment_status = StatusPagamento::Aprovado;
        $pedido->paid_at = $pagamento->aprovadoEm ?: now();
        $pedido->save();
    }

    private function expirar(Pedido $pedido): void
    {
        $pedido->status = StatusPedido::Expirado;
        $pedido->payment_status = StatusPagamento::Expirado;
        $pedido->save();
    }

    private function atualizar(Pedido $pedido, StatusPagamento $status): void
    {
        $pedido->payment_status = $status;
        $pedido->save();
    }
}
