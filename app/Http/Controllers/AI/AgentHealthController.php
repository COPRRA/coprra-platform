<?php

declare(strict_types=1);

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\Services\AgentLifecycleService;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controller for AI agent health monitoring and lifecycle management endpoints.
 */
class AgentHealthController extends Controller
{
    private readonly AgentLifecycleService $lifecycleService;
    private readonly AIService $aiService;

    public function __construct(
        AgentLifecycleService $lifecycleService,
        AIService $aiService
    ) {
        $this->lifecycleService = $lifecycleService;
        $this->aiService = $aiService;
    }

    /**
     * Get comprehensive health status for all AI agents.
     */
    public function healthStatus(): JsonResponse
    {
        try {
            $healthStatus = $this->lifecycleService->getAgentHealthStatus();
            $serviceStatus = $this->aiService->getServiceStatus();
            $healthMetrics = $this->aiService->getHealthMetrics();

            $response = [
                'status' => 'success',
                'timestamp' => now()->toISOString(),
                'agent_health' => $healthStatus,
                'service_status' => $serviceStatus,
                'health_metrics' => $healthMetrics,
                'overall_status' => $this->determineOverallStatus($healthStatus, $serviceStatus),
            ];

            return response()->json($response, 200);
        } catch (\Exception $e) {
            Log::error('❌ Health status check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Health status check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed health metrics for a specific agent.
     */
    public function agentHealth(string $agentId): JsonResponse
    {
        try {
            $healthStatus = $this->lifecycleService->getAgentHealthStatus();

            if (! isset($healthStatus['agents'][$agentId])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Agent not found',
                ], 404);
            }

            $agentHealth = $healthStatus['agents'][$agentId];

            return response()->json([
                'status' => 'success',
                'timestamp' => now()->toISOString(),
                'agent' => $agentHealth,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Agent health check failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Agent health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lifecycle statistics.
     */
    public function lifecycleStats(): JsonResponse
    {
        try {
            $stats = $this->lifecycleService->getLifecycleStats();

            return response()->json([
                'status' => 'success',
                'timestamp' => now()->toISOString(),
                'lifecycle_stats' => $stats,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Lifecycle stats retrieval failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Lifecycle stats retrieval failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a specific agent.
     */
    public function pauseAgent(Request $request, string $agentId): JsonResponse
    {
        try {
            $success = $this->lifecycleService->pauseAgent($agentId);

            if (! $success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to pause agent or agent not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Agent paused successfully',
                'agent_id' => $agentId,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Agent pause failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Agent pause failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume a paused agent.
     */
    public function resumeAgent(Request $request, string $agentId): JsonResponse
    {
        try {
            $success = $this->lifecycleService->resumeAgent($agentId);

            if (! $success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to resume agent or agent not paused',
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Agent resumed successfully',
                'agent_id' => $agentId,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Agent resume failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Agent resume failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize or restart a specific agent.
     */
    public function initializeAgent(Request $request, string $agentId): JsonResponse
    {
        try {
            $success = $this->lifecycleService->initializeAgent($agentId);

            if (! $success) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to initialize agent',
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Agent initialized successfully',
                'agent_id' => $agentId,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Agent initialization failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Agent initialization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recover all failed agents.
     */
    public function recoverFailedAgents(): JsonResponse
    {
        try {
            $recoveryResults = $this->lifecycleService->recoverFailedAgents();

            return response()->json([
                'status' => 'success',
                'message' => 'Agent recovery completed',
                'recovery_results' => $recoveryResults,
                'timestamp' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Agent recovery failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Agent recovery failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record heartbeat for an agent.
     */
    public function recordHeartbeat(Request $request, string $agentId): JsonResponse
    {
        try {
            $metrics = $request->input('metrics', []);

            $this->lifecycleService->recordHeartbeat($agentId, $metrics);

            return response()->json([
                'status' => 'success',
                'message' => 'Heartbeat recorded',
                'agent_id' => $agentId,
                'timestamp' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Heartbeat recording failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Heartbeat recording failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get circuit breaker status for all services.
     */
    public function circuitBreakerStatus(): JsonResponse
    {
        try {
            $serviceStatus = $this->aiService->getServiceStatus();
            $circuitBreakerMetrics = $this->aiService->getCircuitBreakerMetrics();

            return response()->json([
                'status' => 'success',
                'timestamp' => now()->toISOString(),
                'service_status' => $serviceStatus,
                'circuit_breaker_metrics' => $circuitBreakerMetrics,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Circuit breaker status check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Circuit breaker status check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset circuit breaker for a specific service.
     */
    public function resetCircuitBreaker(Request $request, string $serviceName): JsonResponse
    {
        try {
            $this->aiService->resetCircuitBreaker($serviceName);

            return response()->json([
                'status' => 'success',
                'message' => 'Circuit breaker reset successfully',
                'service' => $serviceName,
                'timestamp' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Circuit breaker reset failed', [
                'service' => $serviceName,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Circuit breaker reset failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get error summary for all AI operations.
     */
    public function errorSummary(): JsonResponse
    {
        try {
            $errorSummary = $this->aiService->getErrorSummary();

            return response()->json([
                'status' => 'success',
                'timestamp' => now()->toISOString(),
                'error_summary' => $errorSummary,
            ], 200);
        } catch (\Exception $e) {
            Log::error('❌ Error summary retrieval failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error summary retrieval failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recover agent state from persistent storage.
     */
    public function recoverAgentState(string $agentId): JsonResponse
    {
        try {
            $recovered = $this->lifecycleService->recoverAgentState($agentId);

            if ($recovered) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Agent state recovered successfully',
                    'agent_id' => $agentId,
                    'timestamp' => now()->toISOString(),
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'message' => 'No valid state found for recovery',
                'agent_id' => $agentId,
                'timestamp' => now()->toISOString(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to recover agent state', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to recover agent state',
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect state corruption for a specific agent.
     */
    public function detectStateCorruption(string $agentId): JsonResponse
    {
        try {
            $corruptionDetected = $this->lifecycleService->detectStateCorruption($agentId);

            return response()->json([
                'status' => 'success',
                'agent_id' => $agentId,
                'corruption_detected' => $corruptionDetected,
                'message' => $corruptionDetected
                    ? 'State corruption detected and repair attempted'
                    : 'No state corruption detected',
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to detect state corruption', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to detect state corruption',
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Perform automatic recovery for all failed agents.
     */
    public function performAutomaticRecovery(): JsonResponse
    {
        try {
            $recoveryResults = $this->lifecycleService->performAutomaticRecovery();

            $successCount = \count(array_filter($recoveryResults, static function ($result) {
                return 'success' === $result['status'];
            }));

            return response()->json([
                'status' => 'success',
                'message' => 'Automatic recovery completed',
                'recovery_results' => $recoveryResults,
                'total_agents' => \count($recoveryResults),
                'successful_recoveries' => $successCount,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to perform automatic recovery', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to perform automatic recovery',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate graceful shutdown for all agents.
     */
    public function initiateGracefulShutdown(Request $request): JsonResponse
    {
        try {
            $timeout = $request->input('timeout', 30);
            $shutdownResults = $this->lifecycleService->initiateGracefulShutdown($timeout);

            return response()->json([
                'status' => 'success',
                'message' => 'Graceful shutdown initiated',
                'shutdown_results' => $shutdownResults,
                'timeout_seconds' => $timeout,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to initiate graceful shutdown', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate graceful shutdown',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determine overall system status based on agent and service health.
     *
     * @param array<string, mixed> $agentHealth
     * @param array<string, mixed> $serviceStatus
     */
    private function determineOverallStatus(array $agentHealth, array $serviceStatus): string
    {
        // Check agent health
        if ('unhealthy' === $agentHealth['overall_health']) {
            return 'critical';
        }

        if ('degraded' === $agentHealth['overall_health']) {
            return 'degraded';
        }

        // Check service availability
        $unavailableServices = 0;
        foreach ($serviceStatus as $service => $status) {
            if (! $status['available']) {
                ++$unavailableServices;
            }
        }

        if ($unavailableServices > 0) {
            if ($unavailableServices >= \count($serviceStatus) / 2) {
                return 'critical';
            }

            return 'degraded';
        }

        return 'healthy';
    }
}
