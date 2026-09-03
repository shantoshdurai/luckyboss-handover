<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Return 401 if not authenticated
        if (!$request->user()) {
            abort(401, 'Authentication required.');
        }

        // Return 403 if authenticated but lacks permission
        if (!$request->user()->hasPermission($permission)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}