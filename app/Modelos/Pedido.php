<?php

namespace App\Modelos;

use App\Enumeracoes\StatusPagamento;
use App\Enumeracoes\StatusPedido;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'address_id',
    'status',
    'payment_status',
    'payment_provider',
    'payment_reference',
    'payment_url',
    'subtotal',
    'shipping_value',
    'total',
    'paid_at',
    'cancelled_at',
])]
class Pedido extends Model
{
    protected $table = 'orders';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function endereco(): BelongsTo
    {
        return $this->belongsTo(Endereco::class, 'address_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemPedido::class, 'order_id');
    }

    public function eventosPagamento(): HasMany
    {
        return $this->hasMany(EventoPagamento::class, 'order_id');
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'status' => StatusPedido::class,
            'payment_status' => StatusPagamento::class,
            'subtotal' => 'decimal:2',
            'shipping_value' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
