<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        if ($request->isMethod('GET') && 200 === $response->getStatusCode()) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 3600).' GMT');
        }

        return $response;
    }
}
