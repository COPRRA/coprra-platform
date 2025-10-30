<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SetCacheHeadersTest extends TestCase
{
    public function testSetCacheHeadersMiddlewareAddsCacheHeaders(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new SetCacheHeaders();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Cache-Control'));
    }

    public function testSetCacheHeadersMiddlewarePassesRequestSuccessfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new SetCacheHeaders();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testSetCacheHeadersMiddlewareHandlesDifferentResponseCodes(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new SetCacheHeaders();
        $response = $middleware->handle($request, static function ($req) {
            return response('Not Found', 404);
        });

        self::assertSame(404, $response->getStatusCode());
        self::assertTrue($response->headers->has('Cache-Control'));
    }

    public function testSetCacheHeadersMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = new SetCacheHeaders();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Cache-Control'));
    }

    public function testSetCacheHeadersMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new SetCacheHeaders();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Cache-Control'));
    }
}
