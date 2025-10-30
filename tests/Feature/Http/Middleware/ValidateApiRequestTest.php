<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ValidateApiRequest;
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
final class ValidateApiRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testValidateApiRequestMiddlewareValidatesValidApiRequest(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = $this->app->make(ValidateApiRequest::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidateApiRequestMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new ValidateApiRequest();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidateApiRequestMiddlewareHandlesPutRequests(): void
    {
        $request = Request::create('/api/test/1', 'PUT', [
            'name' => 'Updated Name',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new ValidateApiRequest();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidateApiRequestMiddlewareHandlesPatchRequests(): void
    {
        $request = Request::create('/api/test/1', 'PATCH', [
            'name' => 'Patched Name',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new ValidateApiRequest();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidateApiRequestMiddlewareHandlesDeleteRequests(): void
    {
        $request = Request::create('/api/test/1', 'DELETE');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ValidateApiRequest();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testValidateApiRequestMiddlewareHandlesMissingContentType(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new ValidateApiRequest();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
