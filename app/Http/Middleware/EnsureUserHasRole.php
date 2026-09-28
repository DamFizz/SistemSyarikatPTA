<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Allow the request only if the authenticated user has one of the given roles.
     *
     * Usage in routes: ->middleware('role:hr_admin,super_admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() && $request->user()->hasRole(...$roles), 403);

        return $next($request);
    }
}
