<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (auth()->check() && ! auth()->user()?->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email verification required'], 403);
            }

            return redirect()->route('verification.notice');
        }

        $response = $next($request);
        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
