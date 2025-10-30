<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RTLMiddleware
{
    /** @var array<string> */
    private array $rtlLanguages = ['ar', 'he', 'fa', 'ur'];

    public function handle(Request $request, \Closure $next): Response
    {
        $locale = app()->getLocale();

        if (\in_array($locale, $this->rtlLanguages, true)) {
            view()->share('isRTL', true);
            view()->share('textDirection', 'rtl');
        } else {
            view()->share('isRTL', false);
            view()->share('textDirection', 'ltr');
        }

        $response = $next($request);
        if (! $response instanceof Response) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
