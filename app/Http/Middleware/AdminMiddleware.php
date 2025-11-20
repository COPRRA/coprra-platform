<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->hasRole('admin')) {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}
