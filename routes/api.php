<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgendamentoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EstabelecimentoController;
use App\Http\Controllers\Api\ServicoController;
use App\Http\Controllers\Api\HorarioFuncionamentoController;
use App\Http\Controllers\Api\UserLembreteConfigController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\NotificationController;

// Rotas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Autenticação via Google
Route::post('/auth/google', [AuthController::class, 'loginGoogle']);
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);

Route::get('/servicos', [ServicoController::class, 'index']);

// Buscar estabelecimento por nome ou por ramo
Route::get('/estabelecimentos/explorar', [EstabelecimentoController::class, 'explorar']);

// Detalhes completos do estabelecimento
Route::get('/estabelecimentos/{id}/detalhes', [EstabelecimentoController::class, 'detalhesCompletos']);
Route::get('/estabelecimento/{id}/foto', [EstabelecimentoController::class, 'getFotoEstabelecimento']);

// Rotas protegidas (Só entra com Token Bearer)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/dispositivo/token', [AuthController::class, 'salvarToken']);
    
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // Retorna os dados do próprio usuário logado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::put('/user/update', [PerfilController::class, 'update']);
    Route::post('/user/upload-foto', [PerfilController::class, 'uploadFoto']);
    Route::put('/user/alterar-senha', [PerfilController::class, 'alterarSenha']);
    Route::delete('/user/excluir', [PerfilController::class, 'excluirConta']);

    // Logout (Revoga o token atual)
    Route::post('/logout', [AuthController::class, 'logout']);

    // --- CONFIGURAÇÕES DE LEMBRETE DO USUÁRIO ---
    
    // Obter configuração de lembrete do usuário logado
    Route::get('/user/lembrete-config', [UserLembreteConfigController::class, 'show']);
    
    // Criar ou atualizar configuração de lembrete
    Route::post('/user/lembrete-config', [UserLembreteConfigController::class, 'store']);
    
    // Atualizar configuração de lembrete
    Route::put('/user/lembrete-config', [UserLembreteConfigController::class, 'update']);
    Route::patch('/user/lembrete-config', [UserLembreteConfigController::class, 'update']);
    
    // Remover configuração de lembrete
    Route::delete('/user/lembrete-config', [UserLembreteConfigController::class, 'destroy']);

    // --- AGENDAMENTOS (CLIENTE/GERAL) ---
    
    // Checar disponibilidade 
    Route::get('/agendamentos/disponibilidade', [AgendamentoController::class, 'horariosDisponiveis']);

    // Fazer novo agendamento 
    Route::post('/agendamentos', [AgendamentoController::class, 'store']);

    // Cancelar agendamento
    Route::patch('/agendamentos/{id}/cancelar', [AgendamentoController::class, 'cancelar']);
    
    // Confirmar presença no agendamento
    Route::post('/agendamentos/{id}/confirmar-presenca', [AgendamentoController::class, 'confirmarPresenca']);

    // Método para clientes e profissionais verem seus agendamentos
    Route::get('/agendamentos/consultar-agenda', [AgendamentoController::class, 'minhaAgenda']);

    // Ver horários de funcionamento do estabeleciemtno
    Route::get('/horarios-funcionamento', [HorarioFuncionamentoController::class, 'index']);

    // --- GESTÃO DO PROFISSIONAL ---
    
    Route::middleware(['auth:sanctum', 'profissional'])->group(function () {    
        // Todas as rotas abaixo EXIGEM que o usuário esteja ATIVO
        Route::middleware('ativo')->group(function () {

            Route::patch('/agendamentos/{id}/atualizar-status', [AgendamentoController::class, 'atualizarStatusAgendamento']);

            Route::post('/agendamentos/{id}/falta', [AgendamentoController::class, 'registrarFalta']);
            
            Route::post('/profissional/bloqueios', [AgendamentoController::class, 'criarBloqueio']);
            
            Route::delete('/profissional/bloqueios/{id}', [AgendamentoController::class, 'excluirBloqueio']);

            Route::post('/profissional/agendar-manual', [AgendamentoController::class, 'realizarAgendamentoManual']);

            Route::post('/servicos', [ServicoController::class, 'store']);
            
            Route::patch('/servicos/{id}', [ServicoController::class, 'update']);

            Route::delete('/servicos/{id}', [ServicoController::class, 'destroy']);
    
            Route::post('/horarios-funcionamento', [HorarioFuncionamentoController::class, 'store']);
            
            Route::delete('/horarios-funcionamento/{id}', [HorarioFuncionamentoController::class, 'destroy']);

            Route::post('/servicos/upload-foto', [ServicoController::class, 'uploadFoto']);

            Route::post('/estabelecimento/upload-foto', [EstabelecimentoController::class, 'uploadFoto']);
        
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