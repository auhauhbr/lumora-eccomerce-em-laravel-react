<?php

namespace App\Modelos;

use App\Enumeracoes\TipoMovimentacaoEstoque;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_id', 'order_id', 'type', 'quantity', 'reason'])]
class MovimentacaoEstoque extends Model
{
    protected $table = 'stock_movements';

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'product_id');
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'type' => TipoMovimentacaoEstoque::class,
            'quantity' => 'integer',
        ];
    }
}
