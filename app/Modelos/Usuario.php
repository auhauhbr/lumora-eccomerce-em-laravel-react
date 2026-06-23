<?php

namespace App\Modelos;

use App\Enumeracoes\PapelUsuario;
use BancoDeDados\Fabricas\UsuarioFabrica;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as UsuarioAutenticavel;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class Usuario extends UsuarioAutenticavel
{
    /** @use HasFactory<UsuarioFabrica> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => PapelUsuario::class,
        ];
    }

    public function ehAdministrador(): bool
    {
        return $this->role === PapelUsuario::Administrador;
    }

    public function carrinho(): HasOne
    {
        return $this->hasOne(Carrinho::class, 'user_id');
    }

    public function enderecos(): HasMany
    {
        return $this->hasMany(Endereco::class, 'user_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'user_id');
    }

    protected static function newFactory(): UsuarioFabrica
    {
        return UsuarioFabrica::new();
    }
}
