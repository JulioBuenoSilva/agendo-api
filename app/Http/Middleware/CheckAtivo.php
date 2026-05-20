<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAtivo
{
    public function handle(Request $request, Closure $next)
    {
        // Se o usuário estiver logado e não estiver ativo
        if ($request->user() && !$request->user()->ativo) {
            return response()->json([
                'error' => 'Sua conta está aguardando aprovação.',
                'code' => 'ACCOUNT_INACTIVE' // Código útil para o Flutter tratar a tela
            ], 403);
        }

        return $next($request);
    }
}