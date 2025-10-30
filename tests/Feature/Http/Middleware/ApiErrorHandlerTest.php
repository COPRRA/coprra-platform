<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\ApiErrorHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ApiErrorHandlerTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerPassesRequestSuccessfully(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            return response('Success', 200);
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('Success', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesValidationException(): void
    {
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw ValidationException::withMessages(['field' => ['The field is required.']]);
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(422, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Validation failed', $data['message']);
        self::assertSame('VALIDATION_ERROR', $data['error_code']);
        self::assertArrayHasKey('errors', $data);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesAuthenticationException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new AuthenticationException();
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(401, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Authentication required', $data['message']);
        self::assertSame('UNAUTHENTICATED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesAuthorizationException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new AuthorizationException('Access denied');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(403, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Access denied', $data['message']);
        self::assertSame('UNAUTHORIZED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesModelNotFoundException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new ModelNotFoundException();
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Resource not found', $data['message']);
        self::assertSame('NOT_FOUND', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesNotFoundHttpException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new NotFoundHttpException('Endpoint not found');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Endpoint not found', $data['message']);
        self::assertSame('ENDPOINT_NOT_FOUND', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesMethodNotAllowedException(): void
    {
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new MethodNotAllowedHttpException(['GET']);
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(405, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Method not allowed', $data['message']);
        self::assertSame('METHOD_NOT_ALLOWED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesTooManyRequestsException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new TooManyRequestsHttpException(60, 'Rate limit exceeded');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(429, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Too many requests', $data['message']);
        self::assertSame('RATE_LIMIT_EXCEEDED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesPdoException(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new \PDOException('Database connection failed');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(503, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Database connection error', $data['message']);
        self::assertSame('DATABASE_ERROR', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesGeneralExceptionInProduction(): void
    {
        $this->app->instance('env', 'production');

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new \Exception('Something went wrong');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Internal server error', $data['message']);
        self::assertSame('INTERNAL_ERROR', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testApiErrorHandlerHandlesGeneralExceptionInDevelopment(): void
    {
        $this->app->instance('env', 'local');

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new ApiErrorHandler();
        $response = $middleware->handle($request, static function ($req) {
            throw new \Exception('Something went wrong');
        });

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(500, $response->getStatusCode());
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertFalse($data['success']);
        self::assertSame('Something went wrong', $data['message']);
        self::assertSame('INTERNAL_ERROR', $data['error_code']);
    }
}
