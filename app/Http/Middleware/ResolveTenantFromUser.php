<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // SUPERADMIN opera sin tenant específico
        if ($user->isSuperAdmin()) {
            app()->forgetInstance('tenant');
            $request->attributes->set('tenant', null);

            return $next($request);
        }

        $company = $user->company;

        if (! $company || ! $company->is_active) {
            abort(403, 'Company is not active or not assigned.');
        }

        app()->instance('tenant', $company);
        $request->attributes->set('tenant', $company);

        return $next($request);
    }
}
