<?php

namespace App\Modelos;

use BancoDeDados\Fabricas\ProdutoFabrica;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'description',
    'brand',
    'condition',
    'price',
    'stock',
    'image_url',
    'image_urls',
    'is_active',
])]
class Produto extends Model
{
    /** @use HasFactory<ProdutoFabrica> */
    use HasFactory;

    protected $table = 'products';

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'category_id');
    }

    public function itensCarrinho(): HasMany
    {
        return $this->hasMany(ItemCarrinho::class, 'product_id');
    }

    public function movimentacoesEstoque(): HasMany
    {
        return $this->hasMany(MovimentacaoEstoque::class, 'product_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function estaDisponivel(): bool
    {
        return $this->is_active && $this->stock > 0 && $this->categoria?->is_active;
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'image_urls' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): ProdutoFabrica
    {
        return ProdutoFabrica::new();
    }
}
