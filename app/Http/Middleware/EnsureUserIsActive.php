<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No autenticado → no aplica
        if (! $user) {
            return $next($request);
        }

        // Usuario inactivo → bloqueo global
        if (! $user->is_active) {
            return response()->json([
                'message' => 'User is inactive.',
                'code' => 'USER_INACTIVE',
            ], 403);
        }

        return $next($request);
    }
}
