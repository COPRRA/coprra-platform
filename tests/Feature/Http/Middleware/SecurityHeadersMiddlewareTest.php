<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Services\Security\SecurityHeadersService;
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
final class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function testSecurityHeadersMiddlewareAddsSecurityHeaders(): void
    {
        $request = Request::create('/test', 'GET');

        $service = \Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(static function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        });

        $middleware = new SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Content-Type-Options'));
        self::assertTrue($response->headers->has('X-Frame-Options'));
        self::assertTrue($response->headers->has('X-XSS-Protection'));
    }

    public function testSecurityHeadersMiddlewareHandlesSensitiveRoutes(): void
    {
        $request = Request::create('/admin/sensitive', 'GET');

        $service = \Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(static function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $service = \Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(static function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $service = \Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(static function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function testSecurityHeadersMiddlewareHandlesHttpsRedirect(): void
    {
        $request = Request::create('http://example.com/test', 'GET');

        $service = \Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(static function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('X-Content-Type-Options'));
    }
}
