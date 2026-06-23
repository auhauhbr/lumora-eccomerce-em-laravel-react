<?php

namespace App\Modelos;

use BancoDeDados\Fabricas\CategoriaFabrica;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description', 'is_active'])]
class Categoria extends Model
{
    /** @use HasFactory<CategoriaFabrica> */
    use HasFactory;

    protected $table = 'categories';

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'category_id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): CategoriaFabrica
    {
        return CategoriaFabrica::new();
    }
}
