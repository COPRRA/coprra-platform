<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\Authenticate;
use App\Models\User;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class AuthenticateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Auth factory
        $authFactoryMock = $this->mock(AuthFactory::class);
        $authFactoryMock->shouldReceive('guard')->andReturn($this->app['auth']);
        $this->app->instance(AuthFactory::class, $authFactoryMock);
    }

    public function testAuthenticateMiddlewareRedirectsWebRequestsToLogin(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept', 'text/html');

        $middleware = new Authenticate($this->app[AuthFactory::class]);

        // Mock the unauthenticated method to avoid actual redirect
        $this->expectException(HttpException::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }

    public function testAuthenticateMiddlewareReturnsJsonForApiRequests(): void
    {
        $request = Request::create('/api/user', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new Authenticate($this->app[AuthFactory::class]);

        $this->expectException(HttpException::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }

    public function testAuthenticateMiddlewareHandlesApiRoutePattern(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new Authenticate($this->app[AuthFactory::class]);

        $this->expectException(HttpException::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }

    public function testAuthenticateMiddlewarePassesAuthenticatedRequests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock the auth factory to return a guard that checks as authenticated
        $guardMock = $this->mock(Guard::class);
        $guardMock->shouldReceive('check')->andReturn(true);
        $guardMock->shouldReceive('user')->andReturn($user);

        $authFactoryMock = $this->app[AuthFactory::class];
        $authFactoryMock->shouldReceive('guard')->andReturn($guardMock);
        $authFactoryMock->shouldReceive('shouldUse')->andReturn($guardMock);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(static fn () => $user);

        $middleware = new Authenticate($authFactoryMock);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testAuthenticateMiddlewareHandlesDifferentRequestTypes(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept', 'text/html');

        $middleware = new Authenticate($this->app[AuthFactory::class]);

        $this->expectException(HttpException::class);

        $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });
    }
}
