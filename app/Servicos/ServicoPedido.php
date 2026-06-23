<?php

namespace App\Servicos;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use App\Modelos\Endereco;
use App\Modelos\ItemCarrinho;
use App\Modelos\Pedido;
use App\Modelos\Produto;
use App\Modelos\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServicoPedido
{
    public function __construct(
        private readonly ServicoCarrinho $servicoCarrinho,
        private readonly ServicoEstoque $servicoEstoque,
    ) {}

    public function criarDoCarrinho(Usuario $usuario, Endereco $endereco): Pedido
    {
        return DB::transaction(function () use ($usuario, $endereco): Pedido {
            if ($endereco->user_id !== $usuario->id) {
                abort(404);
            }

            $carrinho = $this->servicoCarrinho->carregar(
                $this->servicoCarrinho->obterOuCriar($usuario),
            );

            if ($carrinho->itens->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => ['O carrinho está vazio.'],
                ]);
            }

            $subtotalCentavos = 0;
            $itensValidados = [];

            foreach ($carrinho->itens as $item) {
                $produto = Produto::query()
                    ->with('categoria')
                    ->lockForUpdate()
                    ->findOrFail($item->product_id);

                $this->validarItem($produto, $item);

                $totalCentavos = (int) round(((float) $item->unit_price) * 100) * $item->quantity;
                $subtotalCentavos += $totalCentavos;
                $itensValidados[] = [$produto, $item, $totalCentavos];
            }

            $subtotal = $subtotalCentavos / 100;
            $frete = 0.00;

            $pedido = Pedido::create([
                'user_id' => $usuario->id,
                'address_id' => $endereco->id,
                'status' => StatusPedido::AguardandoPagamento,
                'payment_status' => StatusPagamento::Pendente,
                'subtotal' => $subtotal,
                'shipping_value' => $frete,
                'total' => $subtotal + $frete,
            ]);

            foreach ($itensValidados as [$produto, $item, $totalCentavos]) {
                $pedido->itens()->create([
                    'product_id' => $produto->id,
                    'product_name' => $produto->name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'total' => $totalCentavos / 100,
                ]);
            }

            $carrinho->itens()->delete();

            return $this->carregar($pedido);
        });
    }

    public function cancelar(Usuario $usuario, Pedido $pedido): Pedido
    {
        if ($pedido->user_id !== $usuario->id) {
            abort(404);
        }

        if ($pedido->status !== StatusPedido::AguardandoPagamento) {
            throw ValidationException::withMessages([
                'order' => ['Este pedido não pode mais ser cancelado por esta rota.'],
            ]);
        }

        $pedido->update([
            'status' => StatusPedido::Cancelado,
            'payment_status' => StatusPagamento::Cancelado,
            'cancelled_at' => now(),
        ]);

        return $this->carregar($pedido);
    }

    public function carregar(Pedido $pedido): Pedido
    {
        return $pedido->load(['endereco', 'itens']);
    }

    public function alterarStatusOperacional(Pedido $pedido, StatusPedido $novoStatus): Pedido
    {
        return DB::transaction(function () use ($pedido, $novoStatus): Pedido {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            $permitidos = $this->transicoesPermitidas($pedido->status);

            if (! in_array($novoStatus, $permitidos, true)) {
                throw ValidationException::withMessages([
                    'status' => ['Transição de status não permitida.'],
                ]);
            }

            if ($novoStatus === StatusPedido::Cancelado) {
                if ($pedido->payment_status === StatusPagamento::Aprovado) {
                    $this->servicoEstoque->restaurarParaPedido($pedido);
                    $pedido->payment_status = StatusPagamento::Reembolsado;
                } else {
                    $pedido->payment_status = StatusPagamento::Cancelado;
                }

                $pedido->cancelled_at = now();
            }

            $pedido->status = $novoStatus;
            $pedido->save();

            return $this->carregar($pedido);
        });
    }

    /** @return array<int, StatusPedido> */
    private function transicoesPermitidas(StatusPedido $status): array
    {
        return match ($status) {
            StatusPedido::AguardandoPagamento => [StatusPedido::Cancelado, StatusPedido::Expirado],
            StatusPedido::Pago => [StatusPedido::EmProcessamento, StatusPedido::Cancelado],
            StatusPedido::EmProcessamento => [StatusPedido::Enviado, StatusPedido::Cancelado],
            StatusPedido::Enviado => [StatusPedido::Entregue],
            default => [],
        };
    }

    private function validarItem(Produto $produto, ItemCarrinho $item): void
    {
        if (! $produto->is_active || ! $produto->categoria?->is_active) {
            throw ValidationException::withMessages([
                'cart' => ["O produto {$produto->name} não está mais disponível."],
            ]);
        }

        if ($item->quantity > $produto->stock) {
            throw ValidationException::withMessages([
                'cart' => ["O estoque de {$produto->name} não atende mais à quantidade solicitada."],
            ]);
        }
    }
}
