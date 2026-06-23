<?php

namespace App\Modelos;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id'])]
class Carrinho extends Model
{
    protected $table = 'carts';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemCarrinho::class, 'cart_id');
    }
}
