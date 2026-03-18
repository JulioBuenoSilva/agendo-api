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
            'hora_abertura' => 'required|date_format:H:i:s',
            'hora_fechamento' => 'required|date_format:H:i:s|after:hora_abertura',
        ]);

        $inicio = $request->hora_abertura;
        $fim = $request->hora_fechamento;

        $conflito = HorarioFuncionamento::where('estabelecimento_id', $user->estabelecimento_id)
            ->where('dia_semana', $request->dia_semana)
            ->where(function ($query) use ($inicio, $fim) {
                $query->where('hora_abertura', '<', $fim)
                    ->where('hora_fechamento', '>', $inicio);
            })
            ->exists();

        if ($conflito) {
            return response()->json([
                'message' => 'Já existe um horário que se sobrepõe a este intervalo.'
            ], 422);
        }

        $horario = HorarioFuncionamento::create([
            'estabelecimento_id' => $user->estabelecimento_id,
            'dia_semana' => $request->dia_semana,
            'hora_abertura' => $inicio,
            'hora_fechamento' => $fim,
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