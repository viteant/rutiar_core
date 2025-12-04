<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No autenticado → no aplica
        if (! $user) {
            return $next($request);
        }

        // Usuario que ya cambió contraseña → vida normal
        if (! $user->must_change_password) {
            return $next($request);
        }

        // Rutas que SÍ se permiten aunque falte cambiar contraseña
        // Path real incluye "api/"
        if ($request->is('api/auth/me')
            || $request->is('api/auth/logout')
            || $request->is('api/auth/change-password')
        ) {
            return $next($request);
        }

        // Todo lo demás bloqueado
        return response()->json([
            'message' => 'Debes cambiar tu contraseña antes de continuar.',
            'code' => 'PASSWORD_CHANGE_REQUIRED',
        ], 423);
    }
}
