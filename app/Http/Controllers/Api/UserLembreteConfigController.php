<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
use App\Models\UserLembreteConfig;
use Illuminate\Http\Request;

class UserLembreteConfigController extends Controller
{
    /**
     * Retorna a configuração de lembrete do usuário logado.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();
        
        $config = UserLembreteConfig::where('user_id', $user->id)->first();
        
        if (!$config) {
            return response()->json([
                'message' => 'Configuração não encontrada. Use POST para criar uma nova configuração.',
                'config' => null
            ], 404);
        }
        
        return response()->json($config);
    }

    /**
     * Cria ou atualiza a configuração de lembrete do usuário logado.
     * Como cada usuário só pode ter uma configuração, usa updateOrCreate.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'minutos_antes' => 'required|integer|min:1|max:10080', // Máximo 7 dias (10080 minutos)
        ]);

        $config = UserLembreteConfig::updateOrCreate(
            ['user_id' => $user->id],
            ['minutos_antes' => $request->minutos_antes]
        );

        return response()->json([
            'message' => 'Configuração salva com sucesso.',
            'config' => $config
        ], 201);
    }

    /**
     * Atualiza a configuração de lembrete do usuário logado.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $config = UserLembreteConfig::where('user_id', $user->id)->firstOrFail();
        
        $request->validate([
            'minutos_antes' => 'required|integer|min:1|max:10080', // Máximo 7 dias (10080 minutos)
        ]);

        $config->update([
            'minutos_antes' => $request->minutos_antes
        ]);

        return response()->json([
            'message' => 'Configuração atualizada com sucesso.',
            'config' => $config
        ]);
    }

    /**
     * Remove a configuração de lembrete do usuário logado.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        
        $config = UserLembreteConfig::where('user_id', $user->id)->firstOrFail();
        
        $config->delete();

        return response()->json([
            'message' => 'Configuração removida com sucesso.'
        ]);
    }
}
