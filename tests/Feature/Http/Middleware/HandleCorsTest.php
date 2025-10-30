<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\HandleCors;
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
final class HandleCorsTest extends TestCase
{
    use RefreshDatabase;

    public function testHandleCorsMiddlewareAddsCorsHeaders(): void
    {
        $request = Request::create('/api/test', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $middleware = $this->app->make(HandleCors::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Methods'));
        self::assertTrue($response->headers->has('Access-Control-Allow-Headers'));
    }

    public function testHandleCorsMiddlewareHandlesPreflightRequests(): void
    {
        $request = Request::create('/api/test', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');
        $request->headers->set('Access-Control-Request-Headers', 'Content-Type, Authorization');

        $middleware = $this->app->make(HandleCors::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('POST', $response->headers->get('Access-Control-Allow-Methods'));
        self::assertStringContainsString('Content-Type', $response->headers->get('Access-Control-Allow-Headers'));
    }

    public function testHandleCorsMiddlewarePassesRegularRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Origin', 'https://example.com');

        $middleware = $this->app->make(HandleCors::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
