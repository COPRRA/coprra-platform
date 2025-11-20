<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends BaseApiController
{
    /**
     * Render interactive API documentation using Swagger UI.
     */
    public function index()
    {
        $specPath = storage_path('api-docs/api-docs.json');

        if (! file_exists($specPath)) {
            return response()->json([
                'success' => false,
                'message' => 'OpenAPI spec not found',
                'hint' => 'Expected file at storage/api-docs/api-docs.json',
            ], 404);
        }

        $specJson = file_get_contents($specPath) ?: '{}';

        return response()->view('api.documentation', [
            'specJson' => $specJson,
            'title' => 'COPRRA API Documentation',
        ]);
    }

    public function health(): JsonResponse
    {
        $timestamp = Date::now()->toISOString();
        $version = '1.0.0';

        // Environment
        $environment = (string) config('app.env');

        // Database health
        $database = 'connected';

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $database = 'disconnected';
        }

        // Cache health
        $cache = 'working';

        try {
            Cache::put('health_check', 'ok', 60);
            $cacheValue = Cache::get('health_check');
            if ('ok' !== $cacheValue) {
                $cache = 'not_working';
            }
        } catch (\Throwable $e) {
            $cache = 'not_working';
        }

        // Storage health
        $storage = 'writable';

        try {
            Storage::disk('local')->put('health_check.txt', 'ok');
        } catch (\Throwable $e) {
            $storage = 'not_writable';
        }

        $isHealthy = 'connected' === $database && 'working' === $cache && 'writable' === $storage;
        $statusCode = $isHealthy ? 200 : 503;

        $data = [
            'status' => $isHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => $timestamp,
            'version' => $version,
            'environment' => $environment,
            'database' => $database,
            'cache' => $cache,
            'storage' => $storage,
        ];

        return $isHealthy
            ? $this->success($data, 'System is healthy')
            : $this->error('System is unhealthy', $data, 503);
    }
}
