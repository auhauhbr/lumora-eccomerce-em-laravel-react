<?php

use App\Http\Controladores\Admin\CategoriaAdminControlador;
use App\Http\Controladores\Admin\DashboardAdminControlador;
use App\Http\Controladores\Admin\EstoqueAdminControlador;
use App\Http\Controladores\Admin\MarcaAdminControlador;
use App\Http\Controladores\Admin\PagamentoAdminControlador;
use App\Http\Controladores\Admin\PedidoAdminControlador;
use App\Http\Controladores\Admin\ProdutoAdminControlador;
use App\Http\Controladores\Api\AutenticacaoControlador;
use App\Http\Controladores\Api\CarrinhoControlador;
use App\Http\Controladores\Api\CategoriaControlador;
use App\Http\Controladores\Api\EnderecoControlador;
use App\Http\Controladores\Api\MarcaControlador;
use App\Http\Controladores\Api\PagamentoControlador;
use App\Http\Controladores\Api\PedidoControlador;
use App\Http\Controladores\Api\ProdutoControlador;
use App\Http\Controladores\Api\WebhookControlador;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AutenticacaoControlador::class, 'registrar']);
Route::post('/login', [AutenticacaoControlador::class, 'entrar']);

Route::get('/categories', [CategoriaControlador::class, 'listar']);
Route::get('/brands', [MarcaControlador::class, 'listar']);
Route::get('/products', [ProdutoControlador::class, 'listar']);
Route::get('/products/{produto}', [ProdutoControlador::class, 'detalhar']);
Route::post('/webhooks/mercado-pago', [WebhookControlador::class, 'mercadoPago']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AutenticacaoControlador::class, 'sair']);
    Route::get('/me', [AutenticacaoControlador::class, 'eu']);

    Route::get('/cart', [CarrinhoControlador::class, 'mostrar']);
    Route::post('/cart/items', [CarrinhoControlador::class, 'adicionar']);
    Route::patch('/cart/items/{item}', [CarrinhoControlador::class, 'atualizar']);
    Route::delete('/cart/items/{item}', [CarrinhoControlador::class, 'remover']);
    Route::delete('/cart', [CarrinhoControlador::class, 'limpar']);

    Route::get('/addresses/zipcode/{cep}', [EnderecoControlador::class, 'consultarCep']);
    Route::get('/addresses', [EnderecoControlador::class, 'listar']);
    Route::post('/addresses', [EnderecoControlador::class, 'criar']);
    Route::delete('/addresses/{endereco}', [EnderecoControlador::class, 'excluir']);

    Route::post('/orders', [PedidoControlador::class, 'criar']);
    Route::get('/orders', [PedidoControlador::class, 'listar']);
    Route::get('/orders/{pedido}', [PedidoControlador::class, 'detalhar']);
    Route::post('/orders/{pedido}/cancel', [PedidoControlador::class, 'cancelar']);
    Route::post('/orders/{pedido}/payment', [PagamentoControlador::class, 'criar']);
    Route::get('/orders/{pedido}/payment-status', [PagamentoControlador::class, 'status']);

    Route::prefix('admin')->middleware('admin')->group(function (): void {
        Route::get('/dashboard', [DashboardAdminControlador::class, 'mostrar']);

        Route::get('/categories', [CategoriaAdminControlador::class, 'listar']);
        Route::post('/categories', [CategoriaAdminControlador::class, 'criar']);
        Route::put('/categories/{categoria}', [CategoriaAdminControlador::class, 'atualizar']);
        Route::patch('/categories/{categoria}/toggle-active', [CategoriaAdminControlador::class, 'alternarAtiva']);
        Route::delete('/categories/{categoria}', [CategoriaAdminControlador::class, 'excluir']);

        Route::get('/brands', [MarcaAdminControlador::class, 'listar']);
        Route::post('/brands', [MarcaAdminControlador::class, 'criar']);
        Route::put('/brands/{marca}', [MarcaAdminControlador::class, 'atualizar']);
        Route::patch('/brands/{marca}/toggle-active', [MarcaAdminControlador::class, 'alternarAtiva']);

        Route::get('/products', [ProdutoAdminControlador::class, 'listar']);
        Route::post('/products', [ProdutoAdminControlador::class, 'criar']);
        Route::put('/products/{produto}', [ProdutoAdminControlador::class, 'atualizar']);
        Route::patch('/products/{produto}/toggle-active', [ProdutoAdminControlador::class, 'alternarAtivo']);
        Route::post('/products/{produto}/stock-adjustment', [EstoqueAdminControlador::class, 'ajustar']);

        Route::get('/orders', [PedidoAdminControlador::class, 'listar']);
        Route::get('/orders/{pedido}', [PedidoAdminControlador::class, 'detalhar']);
        Route::patch('/orders/{pedido}/status', [PedidoAdminControlador::class, 'alterarStatus']);
        Route::get('/payment-events', [PagamentoAdminControlador::class, 'eventos']);
        Route::get('/stock-movements', [PagamentoAdminControlador::class, 'movimentacoesEstoque']);
    });
});
