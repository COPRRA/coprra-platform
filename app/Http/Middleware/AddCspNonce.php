<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AddCspNonce
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): BaseResponse
    {
        $nonce = base64_encode(random_bytes(16));

        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://fonts.bunny.net https://cdnjs.cloudflare.com",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
