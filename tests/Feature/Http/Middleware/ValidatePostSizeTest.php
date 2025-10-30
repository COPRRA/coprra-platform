<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ValidatePostSize;
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
final class ValidatePostSizeTest extends TestCase
{
    use RefreshDatabase;

    public function testValidatePostSizeMiddlewareAllowsValidPostSize(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = new ValidatePostSize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidatePostSizeMiddlewareHandlesGetRequests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new ValidatePostSize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidatePostSizeMiddlewareHandlesPutRequests(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Updated Name',
        ]);

        $middleware = new ValidatePostSize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidatePostSizeMiddlewareHandlesPatchRequests(): void
    {
        $request = Request::create('/test', 'PATCH', [
            'name' => 'Patched Name',
        ]);

        $middleware = new ValidatePostSize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidatePostSizeMiddlewareHandlesDeleteRequests(): void
    {
        $request = Request::create('/test', 'DELETE');

        $middleware = new ValidatePostSize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
