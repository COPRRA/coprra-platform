<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next, string $ability): Response
    {
        if (auth()->check() && auth()->user()?->can($ability)) {
            $response = $next($request);
            if (! $response instanceof Response) {
                throw new \RuntimeException('Middleware must return Response instance');
            }

            return $response;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response('Forbidden', 403);
    }
}
