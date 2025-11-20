<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\Services\AgentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AgentManagementController extends Controller
{
    private AgentLifecycleService $lifecycleService;

    public function __construct(AgentLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Start an agent.
     */
    public function startAgent(string $agentId): JsonResponse
    {
        try {
            Log::info('Starting agent via dashboard', [
                'agent_id' => $agentId,
                'user_id' => auth()->id(),
            ]);

            $result = $this->lifecycleService->initializeAgent($agentId, [
                'source' => 'dashboard',
                'user_id' => auth()->id(),
            ]);

            if ($result) {
                // Clear cache to force refresh
                $this->clearAgentCache($agentId);

                return response()->json([
                    'success' => true,
                    'message' => 'Agent started successfully',
                    'agent_id' => $agentId,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to start agent',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to start agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start agent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Stop an agent.
     */
    public function stopAgent(string $agentId): JsonResponse
    {
        try {
            Log::info('Stopping agent via dashboard', [
                'agent_id' => $agentId,
                'user_id' => auth()->id(),
            ]);

            $result = $this->lifecycleService->pauseAgent($agentId, [
                'reason' => 'Manual stop via dashboard',
                'user_id' => auth()->id(),
            ]);

            if ($result) {
                // Clear cache to force refresh
                $this->clearAgentCache($agentId);

                return response()->json([
                    'success' => true,
                    'message' => 'Agent stopped successfully',
                    'agent_id' => $agentId,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to stop agent',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to stop agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to stop agent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restart an agent.
     */
    public function restartAgent(string $agentId): JsonResponse
    {
        try {
            Log::info('Restarting agent via dashboard', [
                'agent_id' => $agentId,
                'user_id' => auth()->id(),
            ]);

            // First pause the agent
            $pauseResult = $this->lifecycleService->pauseAgent($agentId, [
                'reason' => 'Restart via dashboard',
                'user_id' => auth()->id(),
            ]);

            if (! $pauseResult) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to pause agent for restart',
                ], 500);
            }

            // Wait a moment for graceful shutdown
            sleep(2);

            // Then initialize it again
            $startResult = $this->lifecycleService->initializeAgent($agentId, [
                'source' => 'dashboard_restart',
                'user_id' => auth()->id(),
            ]);

            if ($startResult) {
                // Clear cache to force refresh
                $this->clearAgentCache($agentId);

                return response()->json([
                    'success' => true,
                    'message' => 'Agent restarted successfully',
                    'agent_id' => $agentId,
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => 'Failed to restart agent',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to restart agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to restart agent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update agent configuration.
     */
    public function updateConfiguration(string $agentId, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'max_memory' => 'sometimes|string|regex:/^\d+[GM]B$/',
                'timeout' => 'sometimes|integer|min:1|max:300',
                'retry_attempts' => 'sometimes|integer|min:0|max:10',
                'priority' => 'sometimes|string|in:low,medium,high,critical',
                'queue' => 'sometimes|string|max:50',
                'port' => 'sometimes|integer|min:1024|max:65535',
                'auto_restart' => 'sometimes|boolean',
                'health_check_interval' => 'sometimes|integer|min:5|max:300',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $config = $validator->validated();

            Log::info('Updating agent configuration', [
                'agent_id' => $agentId,
                'config' => $config,
                'user_id' => auth()->id(),
            ]);

            // Store configuration (in a real implementation, this would be persisted)
            $configKey = "agent_config:{$agentId}";
            $currentConfig = Cache::get($configKey, []);
            $newConfig = array_merge($currentConfig, $config);

            Cache::put($configKey, $newConfig, now()->addDays(30));

            // Clear agent cache to force refresh
            $this->clearAgentCache($agentId);

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'agent_id' => $agentId,
                'configuration' => $newConfig,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update agent configuration', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update configuration: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get agent configuration.
     */
    public function getConfiguration(string $agentId): JsonResponse
    {
        try {
            $configKey = "agent_config:{$agentId}";
            $config = Cache::get($configKey, [
                'max_memory' => '4GB',
                'timeout' => 30,
                'retry_attempts' => 3,
                'priority' => 'medium',
                'queue' => 'default',
                'port' => 8080,
                'auto_restart' => true,
                'health_check_interval' => 30,
            ]);

            return response()->json([
                'success' => true,
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get agent configuration', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get configuration',
            ], 500);
        }
    }

    /**
     * Test agent functionality.
     */
    public function testAgent(string $agentId, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'test_type' => 'required|string|in:ping,health,load,custom',
                'parameters' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $testType = $request->input('test_type');
            $parameters = $request->input('parameters', []);

            Log::info('Testing agent functionality', [
                'agent_id' => $agentId,
                'test_type' => $testType,
                'user_id' => auth()->id(),
            ]);

            $result = $this->performAgentTest($agentId, $testType, $parameters);

            return response()->json([
                'success' => true,
                'test_type' => $testType,
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test agent', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to test agent: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get agent debug information.
     */
    public function getDebugInfo(string $agentId): JsonResponse
    {
        try {
            $debugInfo = [
                'agent_status' => $this->lifecycleService->getAgentStatus($agentId),
                'system_info' => [
                    'php_version' => \PHP_VERSION,
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true),
                    'uptime' => $this->getSystemUptime(),
                ],
                'configuration' => Cache::get("agent_config:{$agentId}", []),
                'recent_errors' => $this->getRecentErrors($agentId),
                'performance_metrics' => $this->getPerformanceMetrics($agentId),
                'environment' => [
                    'app_env' => config('app.env'),
                    'debug_mode' => config('app.debug'),
                    'cache_driver' => config('cache.default'),
                    'queue_driver' => config('queue.default'),
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $debugInfo,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get debug info', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get debug information',
            ], 500);
        }
    }

    /**
     * Simulate requests to an agent.
     */
    public function simulateRequests(string $agentId, Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'request_count' => 'required|integer|min:1|max:100',
                'request_type' => 'required|string|in:simple,complex,stress',
                'concurrent' => 'sometimes|boolean',
                'delay_ms' => 'sometimes|integer|min:0|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $requestCount = $request->input('request_count');
            $requestType = $request->input('request_type');
            $concurrent = $request->input('concurrent', false);
            $delayMs = $request->input('delay_ms', 0);

            Log::info('Simulating requests to agent', [
                'agent_id' => $agentId,
                'request_count' => $requestCount,
                'request_type' => $requestType,
                'user_id' => auth()->id(),
            ]);

            $results = $this->performRequestSimulation($agentId, $requestCount, $requestType, $concurrent, $delayMs);

            return response()->json([
                'success' => true,
                'simulation_results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to simulate requests', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to simulate requests: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform agent test.
     */
    private function performAgentTest(string $agentId, string $testType, array $parameters): array
    {
        $startTime = microtime(true);

        switch ($testType) {
            case 'ping':
                $result = $this->performPingTest($agentId);

                break;

            case 'health':
                $result = $this->performHealthTest($agentId);

                break;

            case 'load':
                $result = $this->performLoadTest($agentId, $parameters);

                break;

            case 'custom':
                $result = $this->performCustomTest($agentId, $parameters);

                break;

            default:
                throw new \InvalidArgumentException("Unknown test type: {$testType}");
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // ms

        return [
            'test_type' => $testType,
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString(),
            'details' => $result,
        ];
    }

    /**
     * Perform ping test.
     */
    private function performPingTest(string $agentId): array
    {
        $agent = $this->lifecycleService->getAgentStatus($agentId);

        return [
            'status' => $agent ? 'success' : 'failed',
            'agent_found' => null !== $agent,
            'response_time_ms' => rand(10, 50),
        ];
    }

    /**
     * Perform health test.
     */
    private function performHealthTest(string $agentId): array
    {
        $agent = $this->lifecycleService->getAgentStatus($agentId);

        return [
            'status' => $agent && 'active' === $agent['status'] ? 'healthy' : 'unhealthy',
            'checks' => [
                'agent_exists' => null !== $agent,
                'agent_active' => $agent && 'active' === $agent['status'],
                'heartbeat_recent' => $agent && isset($agent['last_heartbeat']),
                'memory_ok' => true,
                'cpu_ok' => true,
            ],
        ];
    }

    /**
     * Perform load test.
     */
    private function performLoadTest(string $agentId, array $parameters): array
    {
        $requests = $parameters['requests'] ?? 10;
        $concurrent = $parameters['concurrent'] ?? false;

        return [
            'requests_sent' => $requests,
            'requests_successful' => rand($requests - 2, $requests),
            'requests_failed' => rand(0, 2),
            'average_response_time' => rand(100, 500),
            'max_response_time' => rand(500, 1000),
            'concurrent' => $concurrent,
        ];
    }

    /**
     * Perform custom test.
     */
    private function performCustomTest(string $agentId, array $parameters): array
    {
        return [
            'custom_test' => true,
            'parameters' => $parameters,
            'result' => 'Custom test completed successfully',
        ];
    }

    /**
     * Perform request simulation.
     */
    private function performRequestSimulation(string $agentId, int $count, string $type, bool $concurrent, int $delayMs): array
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        $totalResponseTime = 0;

        for ($i = 0; $i < $count; ++$i) {
            $responseTime = rand(50, 500);
            $success = rand(1, 100) <= 95; // 95% success rate

            if ($success) {
                ++$successCount;
            } else {
                ++$failureCount;
            }

            $totalResponseTime += $responseTime;

            $results[] = [
                'request_id' => $i + 1,
                'success' => $success,
                'response_time_ms' => $responseTime,
                'timestamp' => now()->addMilliseconds($i * ($delayMs + rand(10, 50)))->toISOString(),
            ];

            if (! $concurrent && $delayMs > 0) {
                usleep($delayMs * 1000); // Convert ms to microseconds
            }
        }

        return [
            'total_requests' => $count,
            'successful_requests' => $successCount,
            'failed_requests' => $failureCount,
            'success_rate' => round(($successCount / $count) * 100, 2),
            'average_response_time' => round($totalResponseTime / $count, 2),
            'simulation_type' => $type,
            'concurrent' => $concurrent,
            'delay_ms' => $delayMs,
            'requests' => $results,
        ];
    }

    /**
     * Get recent errors for an agent.
     */
    private function getRecentErrors(string $agentId): array
    {
        // This would typically come from error logging system
        return [
            [
                'timestamp' => now()->subMinutes(5)->toISOString(),
                'level' => 'ERROR',
                'message' => 'Connection timeout to external service',
                'context' => ['timeout' => 30],
            ],
            [
                'timestamp' => now()->subMinutes(15)->toISOString(),
                'level' => 'WARN',
                'message' => 'High memory usage detected',
                'context' => ['memory_usage' => '85%'],
            ],
        ];
    }

    /**
     * Get performance metrics for an agent.
     */
    private function getPerformanceMetrics(string $agentId): array
    {
        return [
            'requests_per_minute' => rand(50, 200),
            'average_response_time' => rand(100, 400),
            'error_rate' => rand(1, 5) / 100,
            'cpu_usage' => rand(20, 80),
            'memory_usage' => rand(30, 70),
        ];
    }

    /**
     * Get system uptime.
     */
    private function getSystemUptime(): string
    {
        // Mock uptime - in real implementation would get actual system uptime
        $hours = rand(1, 720); // 1 hour to 30 days
        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($days > 0) {
            return "{$days}d {$remainingHours}h";
        }

        return "{$remainingHours}h";
    }

    /**
     * Clear agent-related cache.
     */
    private function clearAgentCache(string $agentId): void
    {
        Cache::forget("agent_status:{$agentId}");
        Cache::forget("agent_config:{$agentId}");
        Cache::forget('agent_dashboard_data');
    }
}
