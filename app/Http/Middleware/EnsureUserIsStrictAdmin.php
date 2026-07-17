<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStrictAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStrictAdmin()) {
            abort(403, 'Accès réservé aux administrateurs VERGA.');
        }

        return $next($request);
    }
}
