<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\StartSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class StartSessionTest extends TestCase
{
    use RefreshDatabase;

    public function testStartSessionMiddlewareStartsSession(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = $this->app->make(StartSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testStartSessionMiddlewareHandlesSessionData(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));

        $middleware = $this->app->make(StartSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testStartSessionMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = $this->app->make(StartSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testStartSessionMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new StartSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testStartSessionMiddlewareHandlesDifferentRequestMethods(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Updated Name',
        ]);

        $middleware = new StartSession();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
