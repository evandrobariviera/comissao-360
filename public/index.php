<?php

declare(strict_types=1);

use App\Controllers\AjudaController;
use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\DashboardController;
use App\Controllers\FechamentoController;
use App\Controllers\FilialController;
use App\Controllers\IndicadorController;
use App\Controllers\MetaController;
use App\Controllers\MinhaAreaController;
use App\Controllers\MinhaContaController;
use App\Controllers\ParametroController;
use App\Controllers\UsuarioController;
use App\Controllers\VendaController;
use App\Core\Router;

require __DIR__ . '/../app/bootstrap.php';

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/ajuda', [AjudaController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/filiais', [FilialController::class, 'index']);
$router->get('/filiais/novo', [FilialController::class, 'novo']);
$router->post('/filiais', [FilialController::class, 'criar']);
$router->get('/filiais/{id}/editar', [FilialController::class, 'editar']);
$router->post('/filiais/{id}', [FilialController::class, 'atualizar']);
$router->post('/filiais/{id}/status', [FilialController::class, 'alternarStatus']);

$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/novo', [UsuarioController::class, 'novo']);
$router->post('/usuarios', [UsuarioController::class, 'criar']);
$router->get('/usuarios/{id}/editar', [UsuarioController::class, 'editar']);
$router->post('/usuarios/{id}', [UsuarioController::class, 'atualizar']);
$router->post('/usuarios/{id}/status', [UsuarioController::class, 'alternarStatus']);

$router->get('/categorias', [CategoriaController::class, 'index']);
$router->get('/categorias/novo', [CategoriaController::class, 'novo']);
$router->post('/categorias', [CategoriaController::class, 'criar']);
$router->get('/categorias/{id}/editar', [CategoriaController::class, 'editar']);
$router->post('/categorias/{id}', [CategoriaController::class, 'atualizar']);
$router->post('/categorias/{id}/status', [CategoriaController::class, 'alternarStatus']);

$router->get('/vendas', [VendaController::class, 'index']);
$router->post('/vendas', [VendaController::class, 'salvarGrade']);
$router->post('/vendas/bruta', [VendaController::class, 'atualizarBruta']);
$router->post('/vendas/{id}/excluir', [VendaController::class, 'excluir']);

$router->get('/indicadores', [IndicadorController::class, 'index']);
$router->post('/indicadores', [IndicadorController::class, 'salvar']);

$router->get('/fechamento', [FechamentoController::class, 'index']);
$router->post('/fechamento/aprovar', [FechamentoController::class, 'aprovar']);

$router->get('/metas', [MetaController::class, 'index']);
$router->post('/metas', [MetaController::class, 'salvar']);

$router->get('/parametros', [ParametroController::class, 'index']);
$router->post('/parametros', [ParametroController::class, 'salvar']);
$router->post('/parametros/filial', [ParametroController::class, 'salvarFilial']);

$router->get('/minhas-vendas', [MinhaAreaController::class, 'vendas']);
$router->get('/minhas-metas', [MinhaAreaController::class, 'metas']);

$router->get('/minha-conta', [MinhaContaController::class, 'index']);
$router->post('/minha-conta/senha', [MinhaContaController::class, 'trocarSenha']);
$router->post('/minha-conta/avatar', [MinhaContaController::class, 'atualizarAvatar']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/');
