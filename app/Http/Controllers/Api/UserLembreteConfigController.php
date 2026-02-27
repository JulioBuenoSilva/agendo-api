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
        
        $lembretes = UserLembreteConfig::where('user_id', $user->id)->get();
        
        if (!$lembretes) {
            return response()->json([
                'lembretes' => null
            ], 200);
        }
        
        return response()->json([
            'lembretes' => $lembretes
        ]);
    }

    /**
     * Cria  a configuração de lembrete do usuário logado.
     * Um usuário pode ter mais de uma configuração.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'lembretes' => 'required|array|min:1',
            'lembretes.*.minutos_antes' => 'required|integer|min:1|max:10080',
        ]);

        $user->lembretes()->delete();

        $lembretesCriados = [];

        foreach ($request->lembretes as $l) {

            $lembretesCriados[] = $user->lembretes()->create([
                'minutos_antes' => $l['minutos_antes'],
            ]);
        }

        return response()->json([
            'message' => 'Configuração salva com sucesso.',
            'lembretes' => $lembretesCriados
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
