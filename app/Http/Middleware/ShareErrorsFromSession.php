<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareErrorsFromSession
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

        if ($request->hasSession()) {
            $errors = $request->session()->get('errors');
            if ($errors) {
                view()->share('errors', $errors);
            }
        }

        return $response;
    }
}
