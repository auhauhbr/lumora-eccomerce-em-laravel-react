<?php

namespace Testes\Funcionais;

use App\Enumeracoes\PapelUsuario;
use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Testes\TesteBase;

class AutenticacaoApiTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_cliente_pode_se_registrar_e_receber_token(): void
    {
        $resposta = $this->postJson('/api/register', [
            'name' => 'Cliente Lumora',
            'email' => 'cliente@teste.com',
            'password' => 'senha123',
            'password_confirmation' => 'senha123',
        ]);

        $resposta
            ->assertCreated()
            ->assertJsonPath('usuario.email', 'cliente@teste.com')
            ->assertJsonPath('usuario.role', PapelUsuario::Cliente->value)
            ->assertJsonStructure(['token']);
    }

    public function test_login_retorna_token_e_rota_me_retorna_usuario(): void
    {
        Usuario::factory()->create([
            'email' => 'cliente@teste.com',
            'password' => 'senha123',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'cliente@teste.com',
            'password' => 'senha123',
        ]);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('usuario.email', 'cliente@teste.com');
    }

    public function test_cliente_nao_acessa_rota_administrativa(): void
    {
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertForbidden();
    }

    public function test_administrador_acessa_rota_administrativa(): void
    {
        $administrador = Usuario::factory()->administrador()->create();

        $this->actingAs($administrador, 'sanctum')
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'dados' => [
                    'total_vendas',
                    'faturamento_total',
                    'produtos_ativos',
                    'ultimos_pedidos',
                ],
            ]);
    }

    public function test_logout_remove_token_atual(): void
    {
        $cliente = Usuario::factory()->create();
        $token = $cliente->createToken('teste')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
