<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\HorarioFuncionamento;
use Illuminate\Http\Request;

class HorarioFuncionamentoController extends Controller
{
    /**
     * Lista os horários de um estabelecimento (Público).
     */
    public function index(Request $request)
    {
        $request->validate(['estabelecimento_id' => 'required|exists:estabelecimentos,id']);

        $horarios = HorarioFuncionamento::where('estabelecimento_id', $request->estabelecimento_id)
            ->orderBy('dia_semana')
            ->orderBy('hora_abertura')
            ->get();

        return response()->json($horarios);
    }

    /**
     * Salva ou Atualiza horários (Dono).
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'dia_semana' => 'required|integer|between:0,6',
            'hora_abertura' => 'required|date_format:H:i',
            'hora_fechamento' => 'required|date_format:H:i|after:hora_abertura',
        ]);

        $horario = HorarioFuncionamento::create([
            'estabelecimento_id' => $user->estabelecimento_id,
            'dia_semana' => $request->dia_semana,
            'hora_abertura' => $request->hora_abertura,
            'hora_fechamento' => $request->hora_fechamento,
        ]);

        return response()->json($horario, 201);
    }

    /**
     * Remove um horário (Dono).
     */
    public function destroy(Request $request, $id)
    {
        $horario = HorarioFuncionamento::where('id', $id)
            ->where('estabelecimento_id', $request->user()->estabelecimento_id)
            ->firstOrFail();

        $horario->delete();

        return response()->json(['message' => 'Horário removido.']);
    }
}