<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_id', 'quantity', 'unit_price'])]
class ItemCarrinho extends Model
{
    protected $table = 'cart_items';

    public function carrinho(): BelongsTo
    {
        return $this->belongsTo(Carrinho::class, 'cart_id');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }
}
