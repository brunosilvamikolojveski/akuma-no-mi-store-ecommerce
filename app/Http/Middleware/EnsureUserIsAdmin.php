<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role?->name !== 'admin') {
            return response()->json([
                'message' => 'Acesso restrito ao administrador.',
            ], 403);
        }

        return $next($request);
    }
}
