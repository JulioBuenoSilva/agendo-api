<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AprovacaoController extends Controller
{
    /**
     * Aprova o Usuário Dono e consequentemente o Estabelecimento dele.
     * Ação realizada por VOCÊ (Admin do SaaS).
     */
    public function aprovarEstabelecimento(Request $request, $id)
    {
        // TODO: Validar se o $request->user() é você (o admin geral)
        
        $usuarioDono = User::where('id', $id)
            ->where('is_admin_estabelecimento', true)
            ->firstOrFail();

        $usuarioDono->update(['ativo' => true]);

        return response()->json([
            'message' => "Estabelecimento '{$usuarioDono->estabelecimento->nome}' aprovado com sucesso!"
        ]);
    }

    /**
     * Aprova o vínculo de um profissional a um estabelecimento.
     * Ação realizada pelo DONO do estabelecimento.
     */
    public function aprovarProfissional(Request $request, $id)
    {
        $dono = $request->user();

        // Verifica se o logado é realmente dono de algum lugar
        if (!$dono->is_admin_estabelecimento) {
            return response()->json(['error' => 'Apenas o dono do estabelecimento pode aprovar profissionais.'], 403);
        }

        // Busca o profissional que solicitou o vínculo
        $profissional = User::where('id', $id)
            ->where('tipo', 'profissional')
            ->where('estabelecimento_id', $dono->estabelecimento_id)
            ->firstOrFail();

        $profissional->update(['ativo' => true]);

        return response()->json([
            'message' => "O profissional {$profissional->name} agora está ativo na sua equipe."
        ]);
    }
}