<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StartSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (! $request->hasSession()) {
            $request->setLaravelSession(
                app('session.store')
            );
        }

        $response = $next($request);
        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
