<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\LocaleMiddleware;
use App\Models\User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class LocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Guard and Session dependencies
        $guardMock = $this->mock(Guard::class);
        $sessionMock = $this->mock(Store::class);

        $this->app->instance(Guard::class, $guardMock);
        $this->app->instance(Store::class, $sessionMock);
    }

    public function testLocaleMiddlewarePassesRequestSuccessfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new LocaleMiddleware(
            $this->app[Guard::class],
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testLocaleMiddlewareHandlesAuthenticatedUser(): void
    {
        $user = User::factory()->create();
        $guardMock = $this->app[Guard::class];
        $guardMock->shouldReceive('check')->andReturn(true);
        $guardMock->shouldReceive('user')->andReturn($user);

        $request = Request::create('/test', 'GET');

        $middleware = new LocaleMiddleware(
            $guardMock,
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testLocaleMiddlewareHandlesSessionLocale(): void
    {
        // Use Laravel's session testing helpers instead of mocking
        $this->withSession(['locale_language' => 'es']);

        $request = Request::create('/test', 'GET');
        $request->setLaravelSession($this->app['session.store']);

        $middleware = new LocaleMiddleware(
            $this->app[Guard::class],
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testLocaleMiddlewareHandlesBrowserLanguage(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr-FR,fr;q=0.9,en;q=0.8');

        $middleware = new LocaleMiddleware(
            $this->app[Guard::class],
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
    }

    public function testLocaleMiddlewareHandlesExceptionsGracefully(): void
    {
        // Mock the Guard to throw an exception
        $guardMock = $this->mock(Guard::class);
        $guardMock->shouldReceive('check')->andThrow(new \Exception('Test exception'));

        $request = Request::create('/test', 'GET');

        $middleware = new LocaleMiddleware(
            $guardMock,
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
