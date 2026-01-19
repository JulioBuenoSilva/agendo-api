<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\Estabelecimento;

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
     * Busca estabelecimentos por nome (busca parcial).
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function buscarPorNome(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|min:2',
        ]);

        $nome = $request->input('nome');

        $estabelecimentos = Estabelecimento::where('nome', 'LIKE', "%{$nome}%")
            ->select('id', 'nome', 'identificador', 'endereco', 'ramo')
            ->get();

        return response()->json([
            'total' => $estabelecimentos->count(),
            'estabelecimentos' => $estabelecimentos
        ]);
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
                      ->select('id', 'name', 'telefone', 'email');
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
        ]);
    }
}
