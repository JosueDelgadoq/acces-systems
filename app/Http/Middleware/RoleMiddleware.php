<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return redirect('/admin/login');
        }

        $roles = array_slice(func_get_args(), 2);

        if (!$request->user()->hasAnyRole($roles)) {
            abort(403, 'No tienes acceso a esta sección.');
        }

        return $next($request);
    }
}
