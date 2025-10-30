<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\Authorize;
use App\Models\User;
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
final class AuthorizeTest extends TestCase
{
    use RefreshDatabase;

    public function testAuthorizeMiddlewareAllowsAuthorizedRequests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(true);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new Authorize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());

        // Verify that the user's can() method was called with the correct ability
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response is not a forbidden response
        self::assertNotSame(403, $response->getStatusCode());
        self::assertStringNotContainsString('forbidden', strtolower($response->getContent()));
    }

    public function testAuthorizeMiddlewareHandlesUnauthenticatedUsers(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new Authorize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        self::assertSame(403, $response->getStatusCode());

        // Verify the response content indicates forbidden access
        self::assertStringContainsString('forbidden', strtolower($response->getContent()));

        // Verify the request was not processed (no OK response)
        self::assertNotSame('OK', $response->getContent());
    }

    public function testAuthorizeMiddlewareHandlesPostRequests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(false);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->setUserResolver(static fn () => $user);

        $middleware = new Authorize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        self::assertSame(403, $response->getStatusCode());

        // Verify that the user's can() method was called
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response content indicates forbidden access
        self::assertStringContainsString('forbidden', strtolower($response->getContent()));

        // Verify the POST data was not processed
        self::assertNotSame('OK', $response->getContent());
    }

    public function testAuthorizeMiddlewareHandlesApiRequests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(false);
        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(static fn () => $user);

        $middleware = new Authorize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        self::assertSame(403, $response->getStatusCode());

        // Verify that the user's can() method was called
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response is JSON for API requests
        self::assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        // Verify the response content indicates forbidden access
        self::assertStringContainsString('forbidden', strtolower($response->getContent()));
    }

    public function testAuthorizeMiddlewareHandlesNullUser(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(static fn () => null);

        $middleware = new Authorize();
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        self::assertSame(403, $response->getStatusCode());
    }
}
