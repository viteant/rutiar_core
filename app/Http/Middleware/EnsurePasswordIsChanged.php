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

        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName();

        $allowedRoutes = [
            'auth.me',
            'auth.logout',
            'auth.change-password',
        ];

        if ($routeName && in_array($routeName, $allowedRoutes, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Debes cambiar tu contraseña antes de continuar.',
            'code' => 'PASSWORD_CHANGE_REQUIRED',
        ], 423);
    }
}
