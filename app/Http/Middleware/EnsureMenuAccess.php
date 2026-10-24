<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMenuAccess
{
    /**
     * Abort when the authenticated user cannot access the given menu.
     */
    public function handle(Request $request, Closure $next, string $menu): Response
    {
        abort_unless($request->user()?->hasMenuAccess($menu), 403);

        return $next($request);
    }
}
