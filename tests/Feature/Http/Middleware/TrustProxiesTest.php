<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\TrustProxies;
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
final class TrustProxiesTest extends TestCase
{
    use RefreshDatabase;

    public function testTrustProxiesMiddlewareHandlesProxyHeaders(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-For', '192.168.1.1');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $middleware = new TrustProxies();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustProxiesMiddlewareHandlesHttpsForwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $middleware = new TrustProxies();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustProxiesMiddlewareHandlesPortForwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Port', '8080');

        $middleware = new TrustProxies();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustProxiesMiddlewareHandlesHostForwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Host', 'api.example.com');

        $middleware = new TrustProxies();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustProxiesMiddlewareHandlesNoProxyHeaders(): void
    {
        $request = Request::create('http://example.com/test', 'GET');

        $middleware = new TrustProxies();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
