<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AICostLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function index(): JsonResponse
    {
        $checks = [
            'status' => 'operational',
            'timestamp' => now()->toIso8601String(),
            'services' => [],
        ];

        // Database check
        try {
            DB::connection()->getPdo();
            $responseTime = $this->measureTime(static fn () => DB::select('SELECT 1'));
            $checks['services']['database'] = [
                'status' => 'operational',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'degraded';
            $checks['services']['database'] = [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }

        // Cache check
        try {
            Cache::put('status_check', 'ok', 60);
            $value = Cache::get('status_check');
            $responseTime = $this->measureTime(static fn () => Cache::get('status_check'));
            $checks['services']['cache'] = [
                'status' => 'ok' === $value ? 'operational' : 'degraded',
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'degraded';
            $checks['services']['cache'] = [
                'status' => 'down',
                'error' => $e->getMessage(),
            ];
        }

        // AI Service check
        try {
            $dailyRequests = AICostLog::whereDate('created_at', today())->count();
            $dailyCost = AICostLog::getTodayCost();
            $checks['services']['ai'] = [
                'status' => 'operational',
                'daily_requests' => $dailyRequests,
                'daily_cost' => '$'.number_format($dailyCost, 4),
            ];
        } catch (\Exception $e) {
            $checks['services']['ai'] = [
                'status' => 'unknown',
                'error' => 'Cost tracking unavailable',
            ];
        }

        $httpCode = 'operational' === $checks['status'] ? 200 : 503;

        return response()->json($checks, $httpCode);
    }

    private function measureTime(callable $callback): string
    {
        $start = microtime(true);
        $callback();

        return round((microtime(true) - $start) * 1000, 2).'ms';
    }
}
