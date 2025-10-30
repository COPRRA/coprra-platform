<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Legacy index method for web route compatibility.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->check($request);
    }

    /**
     * Comprehensive health check endpoint.
     */
    public function check(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overall = collect($checks)->every(static fn ($check) => 'ok' === $check['status']);

        return response()->json([
            'status' => $overall ? 'ok' : 'error',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
        ], $overall ? 200 : 503);
    }

    /**
     * Simple health check for load balancers.
     */
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Database connectivity check.
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();

            // Test a simple query
            $result = DB::select('SELECT 1 as test');
            $duration = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'response_time_ms' => $duration,
                'connection' => config('database.default'),
            ];
        } catch (\Exception $e) {
            Log::error('Database health check failed', [
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ]);

            return [
                'status' => 'error',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
                'connection' => config('database.default'),
            ];
        }
    }

    /**
     * Cache system check.
     */
    private function checkCache(): array
    {
        try {
            $start = microtime(true);
            $testKey = 'health_check_'.time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'message' => 'Cache system working',
                    'response_time_ms' => $duration,
                    'driver' => config('cache.default'),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache value mismatch',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system failed',
                'error' => $e->getMessage(),
                'driver' => config('cache.default'),
            ];
        }
    }

    /**
     * Storage system check.
     */
    private function checkStorage(): array
    {
        try {
            $start = microtime(true);
            $testFile = 'health_check_'.time().'.txt';
            $testContent = 'health check test';

            \Storage::disk('local')->put($testFile, $testContent);
            $retrieved = \Storage::disk('local')->get($testFile);
            \Storage::disk('local')->delete($testFile);

            $duration = round((microtime(true) - $start) * 1000, 2);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'ok',
                    'message' => 'Storage system working',
                    'response_time_ms' => $duration,
                    'disk' => config('filesystems.default'),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Storage content mismatch',
                'disk' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage system failed',
                'error' => $e->getMessage(),
                'disk' => config('filesystems.default'),
            ];
        }
    }

    /**
     * Queue system check.
     */
    private function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $driver = config("queue.connections.{$connection}.driver");

            return [
                'status' => 'ok',
                'message' => 'Queue system configured',
                'driver' => $driver,
                'connection' => $connection,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Queue system check failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
