<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to the given roles.
     *
     * Usage: `->middleware('role:admin')` or `->middleware('role:owner,employee')`
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(
            fn (string $role): UserRole => UserRole::from($role),
            $roles,
        );

        abort_unless($request->user() !== null && in_array($request->user()->role, $allowed, true), 403);

        return $next($request);
    }
}
