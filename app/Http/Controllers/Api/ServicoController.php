<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Servico;
use Illuminate\Http\Request;

class ServicoController extends Controller
{
    /**
     * Lista todos os serviços de um estabelecimento (Público/Cliente).
     */
    public function index(Request $request)
    {
        $request->validate(['estabelecimento_id' => 'required|exists:estabelecimentos,id']);
        
        $servicos = Servico::where('estabelecimento_id', $request->estabelecimento_id)
            ->where('ativo', true)
            ->get();

        return response()->json($servicos);
    }

    /**
     * Criar novo serviço (Apenas Dono/Profissional do local).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nome' => 'required|string|max:255',
            'duracao_minutos' => 'required|integer|min:1',
            'preco' => 'required|numeric|min:0',
            'observacao' => 'nullable|string|max:1000',
        ]);

        $servico = Servico::create([
            'estabelecimento_id' => $user->estabelecimento_id,
            'nome' => $request->nome,
            'duracao_minutos' => $request->duracao_minutos,
            'preco' => $request->preco,
            'observacao' => $request->observacao,
            'ativo' => true
        ]);

        return response()->json($servico, 201);
    }

    /**
     * Atualizar um serviço.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $servico = Servico::where('id', $id)
            ->where('estabelecimento_id', $user->estabelecimento_id)
            ->firstOrFail();

        $servico->update($request->only(['nome', 'duracao_minutos', 'preco', 'observacao', 'ativo']));

        return response()->json($servico);
    }

    /**
     * "Deletar" (Desativar) um serviço.
     * Recomendo desativar em vez de deletar para não quebrar o histórico de agendamentos.
     */
    public function destroy(Request $request, $id)
    {
        $servico = Servico::where('id', $id)
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->firstOrFail();

        $servico->update(['ativo' => false]);

        return response()->json(['message' => 'Serviço desativado com sucesso.']);
    }
}