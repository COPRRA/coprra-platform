<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\EncryptCookies;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class EncryptCookiesTest extends TestCase
{
    use RefreshDatabase;

    public function testEncryptCookiesMiddlewareEncryptsCookies(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);
        $response = $middleware->handle($request, static function ($req) {
            $response = response('OK', 200);
            $response->cookie('test_cookie', 'test_value');

            return $response;
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->headers->has('Set-Cookie'));

        // Verify that the cookie is actually encrypted
        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);

        $cookie = $cookies[0];
        self::assertSame('test_cookie', $cookie->getName());

        // The cookie value should be encrypted (not equal to the original value)
        self::assertNotSame('test_value', $cookie->getValue());

        // Verify the cookie value is not empty and looks encrypted
        self::assertNotEmpty($cookie->getValue());
        self::assertGreaterThan(10, \strlen($cookie->getValue()));
    }

    public function testEncryptCookiesMiddlewarePassesRequestSuccessfully(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());
    }

    public function testEncryptCookiesMiddlewareHandlesMultipleCookies(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);
        $response = $middleware->handle($request, static function ($req) {
            $response = response('OK', 200);
            $response->cookie('cookie1', 'value1');
            $response->cookie('cookie2', 'value2');

            return $response;
        });

        self::assertSame(200, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        self::assertCount(2, $cookies);

        // Verify both cookies are encrypted
        foreach ($cookies as $cookie) {
            self::assertNotSame('value1', $cookie->getValue());
            self::assertNotSame('value2', $cookie->getValue());

            // Verify the cookie value is not empty and looks encrypted
            self::assertNotEmpty($cookie->getValue());
            self::assertGreaterThan(10, \strlen($cookie->getValue()));
        }
    }

    public function testEncryptCookiesMiddlewareHandlesExistingCookies(): void
    {
        $request = Request::create('/test', 'GET');
        $request->cookies->set('existing_cookie', 'existing_value');

        $encrypter = app(Encrypter::class);
        $middleware = new EncryptCookies($encrypter);
        $response = $middleware->handle($request, static function ($req) {
            return response('OK', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('OK', $response->getContent());

        // Verify that no new cookies were set in the response
        self::assertFalse($response->headers->has('Set-Cookie'));
    }
}
