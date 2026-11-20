<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsOrganizer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isOrganizer()) {
            abort(403, 'Solo los organizadores pueden realizar esta acción.');
        }

        return $next($request);
    }
}
