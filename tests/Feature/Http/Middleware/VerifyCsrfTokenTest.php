<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class VerifyCsrfTokenTest extends TestCase
{
    use RefreshDatabase;

    public function testVerifyCsrfTokenMiddlewareAllowsValidToken(): void
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        // Generate a valid CSRF token
        $token = csrf_token();
        $request->headers->set('X-CSRF-TOKEN', $token);
        $request->merge(['_token' => $token]);

        $middleware = $this->app->make(VerifyCsrfToken::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testVerifyCsrfTokenMiddlewareBlocksInvalidToken(): void
    {
        $this->expectException(TokenMismatchException::class);

        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        // Use an invalid CSRF token
        $request->headers->set('X-CSRF-TOKEN', 'invalid-token');
        $request->merge(['_token' => 'invalid-token']);

        $middleware = $this->app->make(VerifyCsrfToken::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }

    public function testVerifyCsrfTokenMiddlewareAllowsGetRequests(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(VerifyCsrfToken::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testVerifyCsrfTokenMiddlewareAllowsHeadRequests(): void
    {
        $request = Request::create('/test', 'HEAD');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(VerifyCsrfToken::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testVerifyCsrfTokenMiddlewareAllowsOptionsRequests(): void
    {
        $request = Request::create('/test', 'OPTIONS');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(VerifyCsrfToken::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testVerifyCsrfTokenMiddlewareBlocksPostRequestsWithoutToken(): void
    {
        $this->expectException(TokenMismatchException::class);

        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(VerifyCsrfToken::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }
}
