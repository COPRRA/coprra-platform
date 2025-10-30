<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\SetLocaleAndCurrency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\SafeMiddlewareTestBase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class SetLocaleAndCurrencyTest extends SafeMiddlewareTestBase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetLocaleAndCurrencyMiddlewareSetsLocaleAndCurrency(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8');

        $middleware = new SetLocaleAndCurrency();
        $response = $middleware->handle($request, static function ($req) {
            return new Response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetLocaleAndCurrencyMiddlewareHandlesDefaultValues(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new SetLocaleAndCurrency();
        $response = $middleware->handle($request, static function ($req) {
            return new Response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetLocaleAndCurrencyMiddlewareHandlesSessionValues(): void
    {
        $request = Request::create('/test', 'GET');
        $session = new Session(
            new MockArraySessionStorage()
        );
        $request->setSession($session);
        $session->set('locale', 'es');
        $session->set('currency', 'EUR');

        $middleware = new SetLocaleAndCurrency();
        $response = $middleware->handle($request, static function ($req) {
            return new Response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetLocaleAndCurrencyMiddlewareHandlesCookieValues(): void
    {
        $request = Request::create('/test', 'GET');
        $request->cookies->set('locale', 'de');
        $request->cookies->set('currency', 'EUR');

        $middleware = new SetLocaleAndCurrency();
        $response = $middleware->handle($request, static function ($req) {
            return new Response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSetLocaleAndCurrencyMiddlewareHandlesPostRequests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = new SetLocaleAndCurrency();
        $response = $middleware->handle($request, static function ($req) {
            return new Response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }
}
