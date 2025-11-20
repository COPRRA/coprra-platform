<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Service for building API responses with consistent structure.
 */
class ResponseBuilderService
{
    /**
     * Build API response with common structure.
     *
     * @param  array<string|int|float|bool|array|object|* @method static \App\Models\Brand create(array<string, string|bool|null>  $meta
     *
     * @return array<array<array|mixed|object|scalar|* @method static \App\Models\Brand create(array<string, string|bool|null>|bool|int|object|string|* @method static \App\Models\Brand create(array<string, string|bool|null>
     *
     * @psalm-return array{success: bool, message: string, version: '2.0', timestamp: string|null, data?: array|int|object|string, meta?: array<array|object|scalar|* @method static \App\Models\Brand create(array<string, string|bool|null>}
     */
    public function buildApiResponse(
        bool $success,
        string $message,
        array|int|object|string|null $data = null,
        array $meta = []
    ): array {
        $response = [
            'success' => $success,
            'message' => $message,
            'version' => '2.0',
            'timestamp' => now()->toISOString(),
        ];

        if (null !== $data) {
            $response['data'] = $data;
        }

        if ([] !== $meta) {
            $response['meta'] = $meta;
        }

        return $response;
    }

    /**
     * Enhanced success response with v2 features.
     *
     * @param  array<string|int|float|bool|array|object|* @method static \App\Models\Brand create(array<string, string|bool|null>  $meta
     */
    public function successResponse(
        array|int|object|string|null $data = null,
        string $message = 'Success',
        int $statusCode = 200,
        array $meta = []
    ): JsonResponse {
        $response = $this->buildApiResponse(true, $message, $data, $meta);

        return response()->json($response, $statusCode);
    }

    /**
     * Enhanced error response with v2 features.
     *
     * @param  array<string|int|float|bool|array|* @method static \App\Models\Brand create(array<string, string|bool|null>  $meta
     */
    public function errorResponse(
        string $message = 'Error',
        array|string|null $errors = null,
        int $statusCode = 400,
        array $meta = []
    ): JsonResponse {
        $response = $this->buildApiResponse(false, $message, null, $meta);

        if (null !== $errors) {
            $response['errors'] = $errors;
        }

        if ([] !== $meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Enhanced paginated response with v2 features.
     *
     * @param  array<string|int|float|bool|array|* @method static \App\Models\Brand create(array<string, string|bool|null>  $meta
     */
    public function paginatedResponse(
        array|Collection|LengthAwarePaginator $data,
        string $message = 'Success',
        array $meta = []
    ): JsonResponse {
        $paginationService = app(PaginationService::class);
        $pagination = $paginationService->getPaginationData($data);

        $response = [
            'success' => true,
            'message' => $message,
            'data' => \is_object($data) && method_exists($data, 'items') ? $data->items() : [],
            'pagination' => $pagination,
            'version' => '2.0',
            'timestamp' => now()->toISOString(),
        ];

        if ([] !== $meta) {
            $response['meta'] = $meta;
        }

        return response()->json($response);
    }

    /**
     * Add deprecation headers to response.
     */
    public function addDeprecationHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('X-API-Version', '2.0');
        $response->headers->set('X-API-Deprecation-Notice', 'Some features may be deprecated in future versions');

        return $response;
    }
}
