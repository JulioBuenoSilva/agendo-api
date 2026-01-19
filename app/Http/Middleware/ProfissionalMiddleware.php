<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfissionalMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->tipo !== 'profissional') {
            abort(403, 'Acesso negado.');
        }

        if (!$request->user()->ativo) {
            return response()->json(['error' => 'Sua conta ainda não foi aprovada.'], 403);
        }

        return $next($request);
    }
}
