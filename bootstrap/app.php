<?php

declare(strict_types=1);

use App\Console\Commands\StatsCommand;
use App\Console\Commands\UpdatePricesCommand;
use App\Http\Middleware\AddCspNonce;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiErrorHandler;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckUserRole;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\OverrideHealthEndpoint;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SetLocaleAndCurrency;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // Use a non-conflicting health endpoint to avoid overriding /api/health JSON route
        health: '/healthz',
    )
    ->withCommands([
        StatsCommand::class,
        UpdatePricesCommand::class,
    ])
    ->withMiddleware(static function (Middleware $middleware) {
        // Global middleware - applied to all requests
        // Ensure CSP nonce is generated before applying security headers
        // Override health endpoint to ensure JSON at /api/health
        $middleware->append(OverrideHealthEndpoint::class);
        $middleware->append(AddCspNonce::class);
        $middleware->append(SecurityHeadersMiddleware::class);
        // Ensure sessions and error sharing are always available via the web group only
        // (Avoid duplicating session middleware globally which can cause inconsistencies)

        // Web middleware group - include session start and error sharing
        $middleware->web([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            LocaleMiddleware::class,
            SetLocaleAndCurrency::class,
            \App\Http\Middleware\SentryContext::class,
        ]);

        // API middleware group
        $middleware->api([
            EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            SubstituteBindings::class,
            ApiErrorHandler::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'locale' => LocaleMiddleware::class,
            'role' => CheckUserRole::class,
            'permission' => CheckPermission::class,
        ]);
    })
    ->withExceptions(static function (Exceptions $exceptions) {})->create()
;
