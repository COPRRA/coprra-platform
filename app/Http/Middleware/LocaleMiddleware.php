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
        // Get locale from request (header, query param, or session)
        $locale = $request->header('Accept-Language')
            ?? $request->query('locale')
            ?? session('locale')
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
