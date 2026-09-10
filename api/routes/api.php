<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SolicitacaoController;
use App\Http\Controllers\SetorController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ComentarioController;
use App\Http\Controllers\AnexoController;
use App\Http\Controllers\NotificacaoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn (Request $r) => $r->user());

    Route::apiResource('setores', SetorController::class);
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('solicitacoes', SolicitacaoController::class)
    ->parameters(['solicitacoes' => 'solicitacao']);

    Route::post('/solicitacoes/{solicitacao}/aprovar', [SolicitacaoController::class, 'aprovar']);
    Route::post('/solicitacoes/{solicitacao}/rejeitar', [SolicitacaoController::class, 'rejeitar']);
    Route::post('/solicitacoes/{solicitacao}/executar', [SolicitacaoController::class, 'executar']);
    Route::post('/solicitacoes/{solicitacao}/concluir', [SolicitacaoController::class, 'concluir']);
    Route::post('/solicitacoes/{solicitacao}/cancelar', [SolicitacaoController::class, 'cancelar']);
    Route::post('/solicitacoes/{solicitacao}/fechar', [SolicitacaoController::class, 'fechar']);

    Route::get('/solicitacoes/{solicitacao}/comentarios', [ComentarioController::class, 'index']);
    Route::post('/solicitacoes/{solicitacao}/comentarios', [ComentarioController::class, 'store']);

    Route::get('/solicitacoes/{solicitacao}/anexos', [AnexoController::class, 'index']);
    Route::post('/solicitacoes/{solicitacao}/anexos', [AnexoController::class, 'store']);

    Route::get('/notificacoes', [NotificacaoController::class, 'index']);
    Route::post('/notificacoes/{notificacao}/marcar-lida', [NotificacaoController::class, 'marcarLida']);

    Route::get('/relatorios/sla-estourado', [SolicitacaoController::class, 'slaEstourado']);
    Route::get('/relatorios/pendentes', [SolicitacaoController::class, 'pendentesExecucao']);
});