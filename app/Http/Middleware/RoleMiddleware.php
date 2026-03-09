<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return redirect('/admin/login');
        }

        $roles = array_slice(func_get_args(), 2);
        
        if (!in_array($request->user()->role, $roles)) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        return $next($request);
    }
}
