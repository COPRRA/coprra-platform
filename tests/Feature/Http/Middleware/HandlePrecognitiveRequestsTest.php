<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\HandlePrecognitiveRequests;
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
final class HandlePrecognitiveRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function testHandlePrecognitiveRequestsMiddlewarePassesRegularRequests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new HandlePrecognitiveRequests();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testHandlePrecognitiveRequestsMiddlewareHandlesPrecognitiveHeader(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Precognition', 'true');

        $middleware = new HandlePrecognitiveRequests();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testHandlePrecognitiveRequestsMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = new HandlePrecognitiveRequests();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testHandlePrecognitiveRequestsMiddlewareHandlesPutRequests(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $middleware = new HandlePrecognitiveRequests();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testHandlePrecognitiveRequestsMiddlewareHandlesPatchRequests(): void
    {
        $request = Request::create('/test', 'PATCH', [
            'name' => 'Updated Name',
        ]);

        $middleware = new HandlePrecognitiveRequests();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
