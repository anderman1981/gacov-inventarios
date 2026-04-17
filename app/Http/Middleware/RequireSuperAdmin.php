<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check() || ! auth()->user()->isSuperAdmin()) {
            abort(403, 'Acceso restringido al panel de administración de AMR Tech.');
        }

        return $next($request);
    }
}
