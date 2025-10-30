<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        try {
            return $next($request);
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return new JsonResponse([
                    'error' => 'An error occurred',
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                ], 500);
            }

            throw $e;
        }
    }
}
