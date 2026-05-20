<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AprovacaoController extends Controller
{

    public function vincularViaCodigo(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|size:6',
        ]);

        $user = $request->user();
        
        $vinculo = DB::table('vinculo_codigos')
            ->where('user_id', $user->id)
            ->where('codigo', $request->codigo)
            ->where('expires_at', '>', now())
            ->first();

        if (!$vinculo) {
            return response()->json(['error' => 'Código inválido ou expirado.'], 422);
        }

        $user->update([
            'ativo' => true,
            'estabelecimento_id' => $vinculo->estabelecimento_id
        ]);

        DB::table('vinculo_codigos')->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Vínculo confirmado com sucesso!']);
    }
}