<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\V2\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ConcreteBaseApiController extends BaseApiController
{
    public function testMethod(): JsonResponse
    {
        return $this->successResponse(['test' => 'data']);
    }

    public function successResponsePublic(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        return $this->successResponse($data, $message, $statusCode, $meta);
    }

    public function errorResponsePublic(
        string $message = 'Error',
        int $statusCode = 400,
        mixed $errors = null,
        array $meta = []
    ): JsonResponse {
        return $this->errorResponse($message, $statusCode, $errors, $meta);
    }

    public function paginatedResponsePublic(
        mixed $data,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        return $this->paginatedResponse($data, $message, $meta);
    }

    public function getRateLimitInfoPublic(): array
    {
        return $this->getRateLimitInfo();
    }
}

/**
 * @internal
 *
 * @coversNothing
 */
final class BaseApiControllerTest extends TestCase
{
    private ConcreteBaseApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ConcreteBaseApiController();
    }

    /**
     * Test successResponse method returns correct JsonResponse.
     */
    public function testSuccessResponse(): void
    {
        // Arrange
        $data = ['key' => 'value'];
        $message = 'Success message';
        $statusCode = 200;

        // Act
        $response = $this->controller->successResponsePublic($data, $message, $statusCode);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($statusCode, $response->getStatusCode());
        $responseData = $response->getData(true);
        self::assertTrue($responseData['success']);
        self::assertSame($data, $responseData['data']);
        self::assertSame($message, $responseData['message']);
        self::assertSame('2.0', $responseData['version']);
        self::assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Test errorResponse method returns correct JsonResponse.
     */
    public function testErrorResponse(): void
    {
        // Arrange
        $message = 'Error message';
        $statusCode = 400;
        $errors = ['field' => 'error'];

        // Act
        $response = $this->controller->errorResponsePublic($message, $statusCode, $errors);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame($statusCode, $response->getStatusCode());
        $responseData = $response->getData(true);
        self::assertFalse($responseData['success']);
        self::assertSame($message, $responseData['message']);
        self::assertSame($errors, $responseData['errors']);
        self::assertSame('2.0', $responseData['version']);
        self::assertArrayHasKey('timestamp', $responseData);
    }

    /**
     * Test paginatedResponse method returns correct JsonResponse.
     */
    public function testPaginatedResponse(): void
    {
        // Arrange
        $paginator = new LengthAwarePaginator(
            [['id' => 1], ['id' => 2]],
            2,
            15,
            1
        );
        $message = 'Paginated data';

        // Act
        $response = $this->controller->paginatedResponsePublic($paginator, $message);

        // Assert
        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        $responseData = $response->getData(true);
        self::assertTrue($responseData['success']);
        self::assertSame([['id' => 1], ['id' => 2]], $responseData['data']);
        self::assertSame($message, $responseData['message']);
        self::assertSame('2.0', $responseData['version']);
        self::assertArrayHasKey('timestamp', $responseData);
        self::assertArrayHasKey('pagination', $responseData);
    }

    /**
     * Test getRateLimitInfo method returns correct data.
     */
    public function testGetRateLimitInfo(): void
    {
        // Act
        $rateLimit = $this->controller->getRateLimitInfoPublic();

        // Assert
        self::assertIsArray($rateLimit);
        self::assertArrayHasKey('limit', $rateLimit);
        self::assertArrayHasKey('remaining', $rateLimit);
        self::assertArrayHasKey('reset', $rateLimit);
        self::assertArrayHasKey('version', $rateLimit);
        self::assertSame(2000, $rateLimit['limit']);
        self::assertSame('2.0', $rateLimit['version']);
    }
}
