<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ThrottleRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ThrottleRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testThrottleRequestsMiddlewareAllowsRequestsWithinLimit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = $this->app->make(ThrottleRequests::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testThrottleRequestsMiddlewareBlocksRequestsExceedingLimit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.2');

        $middleware = $this->app->make(ThrottleRequests::class);

        // Make multiple requests to exceed the limit
        for ($i = 0; $i < 61; ++$i) {
            $response = $middleware->handle($request, static function ($req) {
                return response('OK', 200);
            });
        }

        self::assertSame(429, $response->getStatusCode());
    }

    public function testThrottleRequestsMiddlewareIncludesRetryAfterHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.3');

        $middleware = $this->app->make(ThrottleRequests::class);

        // Make multiple requests to exceed the limit
        for ($i = 0; $i < 61; ++$i) {
            $response = $middleware->handle($request, static function ($req) {
                return response('OK', 200);
            });
        }

        self::assertSame(429, $response->getStatusCode());
        self::assertTrue($response->headers->has('Retry-After'));
        self::assertSame('60', $response->headers->get('Retry-After'));
        self::assertJsonStringEqualsJsonString(
            '{"message":"Too Many Requests"}',
            $response->getContent()
        );
    }

    public function testThrottleRequestsMiddlewareHandlesDifferentIpsSeparately(): void
    {
        $request1 = Request::create('/test', 'GET');
        $request1->server->set('REMOTE_ADDR', '192.168.1.4');

        $request2 = Request::create('/test', 'GET');
        $request2->server->set('REMOTE_ADDR', '192.168.1.5');

        $middleware = $this->app->make(ThrottleRequests::class);

        // Make requests from first IP
        for ($i = 0; $i < 30; ++$i) {
            $response1 = $middleware->handle($request1, static function ($req) {
                return response('OK', 200);
            });
        }

        // Make requests from second IP
        for ($i = 0; $i < 30; ++$i) {
            $response2 = $middleware->handle($request2, static function ($req) {
                return response('OK', 200);
            });
        }

        self::assertSame(200, $response1->getStatusCode());
        self::assertSame(200, $response2->getStatusCode());
    }

    public function testThrottleRequestsMiddlewareResetsAfterTimeWindow(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.6');

        $middleware = $this->app->make(ThrottleRequests::class);

        // Make requests to exceed the limit
        for ($i = 0; $i < 61; ++$i) {
            $response = $middleware->handle($request, static function ($req) {
                return response('OK', 200);
            });
        }

        self::assertSame(429, $response->getStatusCode());
        self::assertTrue($response->headers->has('Retry-After'));
        self::assertSame('60', $response->headers->get('Retry-After'));
        self::assertJsonStringEqualsJsonString(
            '{"message":"Too Many Requests"}',
            $response->getContent()
        );

        // Verify that the cache has the throttling data
        self::assertTrue(app('cache')->has('throttle:192.168.1.6'));
        self::assertSame(60, app('cache')->get('throttle:192.168.1.6'));
    }
}
