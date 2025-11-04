<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleAndCurrency
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Set currency from user preference, session, or default
        $currency = $request->user()?->currency
            ?? session('currency')
            ?? config('app.default_currency', 'USD');

        if ($currency) {
            session(['currency' => $currency]);
        }

        return $next($request);
    }
}
