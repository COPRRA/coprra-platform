<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Consolidated locale source of truth:
        // 1) User's saved preference -> 2) Session -> 3) Cookie -> 4) Default config
        $locale = $request->user()?->locale
            ?? session('locale')
            ?? $request->cookie('locale')
            ?? config('app.locale');

        // Validate and set locale
        $supportedLocales = config('app.supported_locales', ['en']);
        if (\in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }
}
