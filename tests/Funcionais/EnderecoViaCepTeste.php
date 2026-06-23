<?php

namespace Testes\Funcionais;

use App\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Testes\TesteBase;

class EnderecoViaCepTeste extends TesteBase
{
    use RefreshDatabase;

    public function test_consulta_cep_retorna_endereco_normalizado(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'cep' => '01001-000',
                'logradouro' => 'Praça da Sé',
                'complemento' => 'lado ímpar',
                'bairro' => 'Sé',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ]),
        ]);

        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/addresses/zipcode/01001-000')
            ->assertOk()
            ->assertJsonPath('dados.zip_code', '01001000')
            ->assertJsonPath('dados.street', 'Praça da Sé')
            ->assertJsonPath('dados.city', 'São Paulo')
            ->assertJsonPath('dados.state', 'SP');
    }

    public function test_cep_invalido_ou_nao_encontrado_retorna_erro_amigavel(): void
    {
        $cliente = Usuario::factory()->create();

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/addresses/zipcode/123')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cep');

        Http::fake([
            'viacep.com.br/*' => Http::response(['erro' => true]),
        ]);

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/addresses/zipcode/99999999')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cep');
    }

    public function test_cliente_salva_e_lista_somente_seus_enderecos(): void
    {
        $cliente = Usuario::factory()->create();
        $outroCliente = Usuario::factory()->create();

        $this->actingAs($outroCliente, 'sanctum')
            ->postJson('/api/addresses', $this->dadosEndereco('20040002'))
            ->assertCreated();

        $this->actingAs($cliente, 'sanctum')
            ->postJson('/api/addresses', $this->dadosEndereco('01001000'))
            ->assertCreated()
            ->assertJsonPath('dados.zip_code', '01001000');

        $this->actingAs($cliente, 'sanctum')
            ->getJson('/api/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'dados')
            ->assertJsonPath('dados.0.zip_code', '01001000');
    }

    public function test_cliente_nao_exclui_endereco_de_outro_cliente(): void
    {
        $dono = Usuario::factory()->create();
        $intruso = Usuario::factory()->create();

        $endereco = $dono->enderecos()->create($this->dadosEndereco());

        $this->actingAs($intruso, 'sanctum')
            ->deleteJson("/api/addresses/{$endereco->id}")
            ->assertNotFound();
    }

    /** @return array<string, string> */
    private function dadosEndereco(string $cep = '01001000'): array
    {
        return [
            'zip_code' => $cep,
            'street' => 'Praça da Sé',
            'number' => '100',
            'complement' => 'Conjunto 10',
            'neighborhood' => 'Sé',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];
    }
}
