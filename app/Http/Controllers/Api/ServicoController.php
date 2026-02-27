<?php 
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\Servico;
use Illuminate\Http\Request;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Upload de foto para o serviço
     */

    public function uploadFoto(Request $request)
    {
        $request->validate([
            'servico' => 'required|exists:servicos,id',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Aceita até 5MB, mas vamos diminuir
        ]);

        $user = $request->user();
        $servico = Servico::where('id', $request->servico)
        ->where('estabelecimento_id', $user->estabelecimento_id) // Segurança total
        ->firstOrFail();
        
        // 1. Criar um nome único
        $nomeArquivo = 'servico_' . $servico->id . '_' . time() . '.webp'; // Usar .webp economiza +30%
        $caminhoRelativo = 'servicos/' . $nomeArquivo;

        // 2. Processar a imagem com Intervention Image
        $imagemOtimizada = Image::read($request->file('foto'))
            ->cover(600, 450) // Corta e centraliza em 600x450 (perfeito para formato estilo card)
            ->toWebp(80);     // Converte para WebP com 80% de qualidade

        // 3. Salvar no Storage
        Storage::disk('public')->put($caminhoRelativo, (string) $imagemOtimizada);

        // 4. Limpeza: Deleta a foto anterior
        if ($servico->foto_path) {
            Storage::disk('public')->delete($servico->foto_path);
        }

        $servico->update(['foto_path' => $caminhoRelativo]);

        return response()->json(['url' => asset('storage/' . $caminhoRelativo)]);
    }
}