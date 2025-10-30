<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
// Disabled process isolation on Windows to avoid batch execution issues
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AddQueuedCookiesToResponseTest extends TestCase
{
    use RefreshDatabase;

    public function testAddQueuedCookiesMiddlewareAddsCookiesToResponse(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new AddQueuedCookiesToResponse();
        $response = $middleware->handle($request, static function ($req) {
            $response = new Response('OK', 200);
            $response->headers->setCookie(cookie('test_cookie', 'test_value'));

            return $response;
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Set-Cookie'));
    }

    public function testAddQueuedCookiesMiddlewarePassesRequestSuccessfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new AddQueuedCookiesToResponse();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testAddQueuedCookiesMiddlewareHandlesMultipleCookies(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new AddQueuedCookiesToResponse();
        $response = $middleware->handle($request, static function ($req) {
            $response = new Response('OK', 200);
            $response->headers->setCookie(cookie('cookie1', 'value1'));
            $response->headers->setCookie(cookie('cookie2', 'value2'));

            return $response;
        });

        self::assertSame(200, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertCount(2, $cookies);
    }
}
