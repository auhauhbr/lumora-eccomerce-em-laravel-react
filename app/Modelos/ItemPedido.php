<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'product_name',
    'unit_price',
    'quantity',
    'total',
])]
class ItemPedido extends Model
{
    protected $table = 'order_items';

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'order_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'total' => 'decimal:2',
        ];
    }
}
