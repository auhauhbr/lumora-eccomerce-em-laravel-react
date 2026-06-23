<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'provider',
    'event_type',
    'external_id',
    'payload',
    'processed_at',
])]
class EventoPagamento extends Model
{
    protected $table = 'payment_events';

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'order_id');
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
