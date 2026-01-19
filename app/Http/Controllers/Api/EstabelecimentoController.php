<?php

namespace App\Http\Controllers\Api;

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
}
