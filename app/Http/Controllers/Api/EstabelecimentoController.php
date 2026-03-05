<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Estabelecimento;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EstabelecimentoController extends Controller
{   
    public $estabelecimento;

    public function __construct()
    {
        $this->estabelecimento = New Estabelecimento();
    }
 
    /**
     * Lista os profissionais ativos de um estabelecimento.
     */
    public function listarProfissionais($id)
    {
        $estabelecimento = $this->estabelecimento->findOrFail($id);

        $profissionais = $estabelecimento->profissionais()
            ->where('ativo', true)
            ->get([
                'id',
                'name',
                'telefone',
            ]);

        return response()->json($profissionais);
    }

    /**
     * Explora estabelecimentos com filtros combinados.
     * Cenários: (1) Sem filtros = Lista 10, (2) Só Ramo, (3) Só Nome, (4) Nome + Ramo.
     */
    public function explorar(Request $request)
    {
        $user = $request->user('sanctum');
        $nome = $request->query('nome');
        $ramo = $request->query('ramo');

        $query = Estabelecimento::query()
            ->select(
                'estabelecimentos.id',
                'estabelecimentos.nome',
                'estabelecimentos.identificador',
                'estabelecimentos.endereco',
                'estabelecimentos.ramo',
                'estabelecimentos.foto_path',
            );

        // Filtro por ramo
        if ($ramo && $ramo !== 'Todos') {
            $query->where('estabelecimentos.ramo', $ramo);
        }

        // Filtro por nome
        if ($nome) {
            $query->where('estabelecimentos.nome', 'like', "%{$nome}%");
        }

        // Se estiver logado, prioriza últimos agendamentos
        if ($user) {
            $query->leftJoin('agendamentos', function ($join) use ($user) {
                $join->on('estabelecimentos.id', '=', 'agendamentos.estabelecimento_id')
                    ->where('agendamentos.cliente_id', $user->id);
            })
            ->addSelect(DB::raw('MAX(agendamentos.created_at) as ultimo_agendamento'))
            ->groupBy(
                'estabelecimentos.id',
                'estabelecimentos.nome',
                'estabelecimentos.identificador',
                'estabelecimentos.endereco',
                'estabelecimentos.ramo',
                'estabelecimentos.foto_path'
            )
            ->orderByDesc('ultimo_agendamento');
        } else {
            $query->orderBy('estabelecimentos.nome');
        }

        return response()->json($query->paginate(10));
    }
    
    /**
     * Retorna todas as informações completas de um estabelecimento.
     * Inclui: dados básicos, horários de funcionamento, serviços e profissionais.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detalhesCompletos($id)
    {
        $estabelecimento = Estabelecimento::with([
            'horariosFuncionamento',
            'servicos' => function ($query) {
                $query->where('ativo', true);
            },
            'profissionais' => function ($query) {
                $query->where('ativo', true)
                      ->select('id', 'name', 'telefone', 'email', 'estabelecimento_id', 'foto_path' );
            },
            'dono' => function ($query) {
                $query->select('id', 'name', 'email', 'telefone');
            } 
        ])->findOrFail($id);
 
        return response()->json([
            'id' => $estabelecimento->id,
            'nome' => $estabelecimento->nome,
            'identificador' => $estabelecimento->identificador,
            'endereco' => $estabelecimento->endereco,
            'ramo' => $estabelecimento->ramo,
            'fuso_horario' => $estabelecimento->fuso_horario,
            'horarios_funcionamento' => $estabelecimento->horariosFuncionamento,
            'servicos' => $estabelecimento->servicos,
            'profissionais' => $estabelecimento->profissionais,
            'dono' => $estabelecimento->dono,
            'total_profissionais' => $estabelecimento->profissionais->count(),
            'total_servicos' => $estabelecimento->servicos->count(),
            'foto_url' => $estabelecimento->foto_url
        ]);
    }

    /**
     * Upload de foto para o estabelecimento
     */
    public function uploadFoto(Request $request)
    {
        Log::info('UPLOAD FOTO HIT');
        $request->validate([
            'estabelecimento' => 'required|exists:estabelecimentos,id',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:5120', // Aceita até 5MB, mas vamos diminuir
        ]);

        Log::info($request->user());
        Log::info($request->estabelecimento); 

        $user = $request->user();
        $estabelecimento = Estabelecimento::where('id', $request->estabelecimento)
        ->where('id', $user->estabelecimento_id)
        ->firstOrFail();
        
        // 1. Criar um nome único
        $nomeArquivo = 'estabelecimento_' . $estabelecimento->id . '_' . time() . '.webp'; // Usar .webp economiza +30%
        $caminhoRelativo = 'images/estabelecimentos/' . $nomeArquivo;

        // 2. Processar a imagem com Intervention Image
        $imagemOtimizada = Image::read($request->file('foto'))
            ->cover(600, 450) // Corta e centraliza em 600x450 (perfeito para formato estilo card)
            ->toWebp(80);     // Converte para WebP com 80% de qualidade

        // 3. Salvar no Storage
        Storage::disk('public')->put($caminhoRelativo, (string) $imagemOtimizada);

        // 4. Limpeza: Deleta a foto anterior
        if ($estabelecimento->foto_path) {
            Storage::disk('public')->delete($estabelecimento->foto_path);
        }

        $estabelecimento->update(['foto_path' => $caminhoRelativo]);

        return response()->json(['url' => asset('storage/' . $caminhoRelativo)]);
    }
}
