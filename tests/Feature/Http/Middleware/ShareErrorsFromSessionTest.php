<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ShareErrorsFromSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\NullSessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\SafeMiddlewareTestBase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ShareErrorsFromSessionTest extends SafeMiddlewareTestBase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShareErrorsFromSessionMiddlewareSharesErrors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new NullSessionHandler();
        $request->setLaravelSession($session = new Store('test', $handler));
        $session->put('errors', ['name' => ['The name field is required.']]);

        $middleware = $this->app->make(ShareErrorsFromSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShareErrorsFromSessionMiddlewareHandlesNoErrors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new NullSessionHandler();
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = $this->app->make(ShareErrorsFromSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShareErrorsFromSessionMiddlewareHandlesMultipleErrors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new NullSessionHandler();
        $request->setLaravelSession($session = new Store('test', $handler));
        $session->put('errors', [
            'name' => ['The name field is required.'],
            'email' => ['The email field is required.', 'The email must be a valid email address.'],
        ]);

        $middleware = $this->app->make(ShareErrorsFromSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShareErrorsFromSessionMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $handler = new NullSessionHandler();
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = $this->app->make(ShareErrorsFromSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testShareErrorsFromSessionMiddlewareHandlesApiRequests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = new NullSessionHandler();
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = $this->app->make(ShareErrorsFromSession::class);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
