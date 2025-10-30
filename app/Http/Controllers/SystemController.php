<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SystemController extends Controller
{
    /**
     * Get system information.
     */
    public function getSystemInfo(): JsonResponse
    {
        try {
            $laravelVersion = app()->version();
            $phpVersion = \PHP_VERSION;
            $os = php_uname('s').' '.php_uname('r');
            $serverSoftware = request()->server('SERVER_SOFTWARE') ?? \PHP_SAPI;
            $memoryLimit = \ini_get('memory_limit') ?: 'unknown';
            $maxExecutionTime = (int) (\ini_get('max_execution_time') ?: 0);
            $diskFreeSpace = (string) (disk_free_space(base_path()) ?: 0);
            $diskTotalSpace = (string) (disk_total_space(base_path()) ?: 0);
            $requestTime = request()->server('REQUEST_TIME');
            $uptime = (string) (time() - (int) ($requestTime ?? time()));
            $cpuCount = (int) (getenv('NUMBER_OF_PROCESSORS') ?: 1);
            $loadAverage = \function_exists('sys_getloadavg') ? sys_getloadavg() : [0.0, 0.0, 0.0];

            return response()->json([
                'success' => true,
                'data' => [
                    'laravel_version' => $laravelVersion,
                    'php_version' => $phpVersion,
                    'os' => $os,
                    'server_software' => $serverSoftware,
                    'memory_limit' => $memoryLimit,
                    'max_execution_time' => $maxExecutionTime,
                    'disk_free_space' => $diskFreeSpace,
                    'disk_total_space' => $diskTotalSpace,
                    'uptime' => $uptime,
                    'cpu_count' => $cpuCount,
                    'load_average' => $loadAverage,
                ],
                'message' => 'System information retrieved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get system information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get performance metrics.
     */
    public function getPerformanceMetrics(): JsonResponse
    {
        try {
            $memoryUsage = memory_get_usage();
            $memoryPeak = memory_get_peak_usage();
            $memoryLimit = \ini_get('memory_limit') ?: 'unknown';

            $startRef = \defined('LARAVEL_START') ? (float) LARAVEL_START : (float) (request()->server('REQUEST_TIME_FLOAT') ?? microtime(true));
            $executionTime = (float) (microtime(true) - $startRef);

            $databaseConnections = \count(config('database.connections', []));

            // Cache hits metric is not tracked natively; provide a placeholder integer
            $cacheHits = 0;

            // For HTTP response time, approximate using execution time
            $responseTime = $executionTime;

            return response()->json([
                'success' => true,
                'data' => [
                    'memory_usage' => $memoryUsage,
                    'memory_peak' => $memoryPeak,
                    'memory_limit' => $memoryLimit,
                    'execution_time' => $executionTime,
                    'database_connections' => $databaseConnections,
                    'cache_hits' => $cacheHits,
                    'response_time' => $responseTime,
                ],
                'message' => 'Performance metrics retrieved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting performance metrics: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
