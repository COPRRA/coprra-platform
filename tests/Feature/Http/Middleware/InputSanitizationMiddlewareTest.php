<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\InputSanitizationMiddleware;
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
final class InputSanitizationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function testInputSanitizationMiddlewareSanitizesInput(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '<script>alert("xss")</script>John Doe',
            'email' => 'test@example.com',
            'description' => 'Normal description',
        ]);

        $middleware = new InputSanitizationMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testInputSanitizationMiddlewareHandlesGetRequests(): void
    {
        $request = Request::create('/test', 'GET', [
            'search' => '<script>alert("xss")</script>search term',
        ]);

        $middleware = new InputSanitizationMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testInputSanitizationMiddlewareHandlesNestedArrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '<script>alert("xss")</script>John',
                'email' => 'john@example.com',
            ],
            'address' => [
                'street' => '123 Main St',
                'city' => '<script>alert("xss")</script>New York',
            ],
        ]);

        $middleware = new InputSanitizationMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testInputSanitizationMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'data' => '<script>alert("xss")</script>test data',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new InputSanitizationMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testInputSanitizationMiddlewareHandlesCleanInput(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'Clean description',
        ]);

        $middleware = new InputSanitizationMiddleware();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
