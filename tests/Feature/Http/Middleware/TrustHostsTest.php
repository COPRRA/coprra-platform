<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\TrustHosts;
use Illuminate\Contracts\Foundation\Application;
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
final class TrustHostsTest extends TestCase
{
    use RefreshDatabase;

    public function testTrustHostsMiddlewareTrustsValidHosts(): void
    {
        $request = Request::create('https://example.com/test', 'GET');
        $request->headers->set('Host', 'example.com');

        $app = app(Application::class);
        $middleware = new TrustHosts($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustHostsMiddlewareHandlesDifferentHosts(): void
    {
        $request = Request::create('https://subdomain.example.com/test', 'GET');
        $request->headers->set('Host', 'subdomain.example.com');

        $app = app(Application::class);
        $middleware = new TrustHosts($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustHostsMiddlewareHandlesLocalhost(): void
    {
        $request = Request::create('http://localhost/test', 'GET');
        $request->headers->set('Host', 'localhost');

        $app = app(Application::class);
        $middleware = new TrustHosts($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustHostsMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('https://example.com/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Host', 'example.com');

        $app = app(Application::class);
        $middleware = new TrustHosts($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testTrustHostsMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('https://api.example.com/test', 'GET');
        $request->headers->set('Host', 'api.example.com');
        $request->headers->set('Accept', 'application/json');

        $app = app(Application::class);
        $middleware = new TrustHosts($app);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
