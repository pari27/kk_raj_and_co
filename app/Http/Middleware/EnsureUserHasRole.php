<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = $user && (in_array($user->role->name, $roles, true) || ($user->isSuperAdmin() && in_array('Admin', $roles, true)));

        abort_if(! $allowed, 403);

        return $next($request);
    }
}
