<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgendamentoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServicoController;
use App\Http\Controllers\Api\HorarioFuncionamentoController;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/servicos', [ServicoController::class, 'index']);

// Rotas protegidas (Só entra com Token Bearer)
Route::middleware('auth:sanctum')->group(function () {

    // Retorna os dados do próprio usuário logado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout (Revoga o token atual)
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- AGENDAMENTOS (CLIENTE/GERAL) ---
    
    // Checar disponibilidade 
    Route::get('/agendamentos/disponibilidade', [AgendamentoController::class, 'horariosDisponiveis']);

    // Fazer novo agendamento 
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);

    // Cancelar agendamento
    Route::patch('/agendamentos/{id}/cancelar', [AgendamentoController::class, 'cancelar']);
    
    // Confirmar presença no agendamento
    Route::post('/agendamentos/{id}/confirmar-presenca', [AgendamentoController::class, 'confirmarPresenca']);
    
    // Ver horários de funcionamento do estabeleciemtno
    Route::get('/horarios-funcionamento', [HorarioFuncionamentoController::class, 'index']);

    // --- GESTÃO DO PROFISSIONAL ---
    
    Route::middleware(['auth:sanctum', 'profissional'])->group(function () {    
        // Todas as rotas abaixo EXIGEM que o usuário esteja ATIVO
        Route::middleware('ativo')->group(function () {
    
            Route::get('/profissional/consultar-agenda', [AgendamentoController::class, 'consultarAgendaProfissional']);

            Route::patch('/agendamentos/{id}/atualizar-status', [AgendamentoController::class, 'atualizarStatusAgendamento']);

            Route::post('/profissional/bloqueios', [AgendamentoController::class, 'criarBloqueio']);
            
            Route::delete('/profissional/bloqueios/{id}', [AgendamentoController::class, 'excluirBloqueio']);

            Route::post('/profissional/agendar-manual', [AgendamentoController::class, 'realizarAgendamentoManual']);

            Route::post('/servicos', [ServicoController::class, 'store']);
            
            Route::patch('/servicos/{id}', [ServicoController::class, 'update']);

            Route::delete('/servicos/{id}', [ServicoController::class, 'destroy']);
    
            Route::post('/horarios-funcionamento', [HorarioFuncionamentoController::class, 'store']);
            
            Route::delete('/horarios-funcionamento/{id}', [HorarioFuncionamentoController::class, 'destroy']);

        
        });
        
    });
    // --- NOTIFICAÇÕES (BASEADAS NO TOKEN) ---
    
    // Listar notificações (Removido {user_id} para segurança)
    Route::get('/notificacoes', function (Request $request) {
        return response()->json($request->user()->notifications);
    });

    // Marcar notificação específica como lida
    Route::patch('/notificacoes/{id}/ler', function (Request $request, $id) {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['mensagem' => 'Notificação lida']);
    });
});