<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Service for monitoring AI operations, performance, and health metrics.
 */
class AIMonitoringService
{
    private const CACHE_PREFIX = 'ai_monitoring:';
    private const METRICS_TTL = 3600; // 1 hour
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? app(LoggerInterface::class);
    }

    /**
     * Record a successful AI operation.
     *
     * @param float                $responseTime Response time in milliseconds
     * @param array<string, mixed> $metadata
     */
    public function recordSuccess(string $operation, float $responseTime, array $metadata = []): void
    {
        $this->incrementCounter("success:{$operation}");
        $this->recordResponseTime($operation, $responseTime);
        $this->updateLastSuccess($operation);

        $this->logger->info('📊 AI Operation Success Recorded', [
            'operation' => $operation,
            'response_time_ms' => $responseTime,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record a failed AI operation.
     *
     * @param array<string, mixed> $metadata
     */
    public function recordFailure(string $operation, string $errorType, string $errorMessage, array $metadata = []): void
    {
        $this->incrementCounter("failure:{$operation}");
        $this->incrementCounter("error_type:{$errorType}");
        $this->updateLastFailure($operation, $errorType, $errorMessage);

        $this->logger->warning('📊 AI Operation Failure Recorded', [
            'operation' => $operation,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Record circuit breaker state change.
     */
    public function recordCircuitBreakerStateChange(string $service, string $oldState, string $newState): void
    {
        $this->incrementCounter("circuit_breaker:{$newState}");

        $cacheKey = self::CACHE_PREFIX."circuit_breaker_state:{$service}";
        Cache::put($cacheKey, [
            'state' => $newState,
            'previous_state' => $oldState,
            'timestamp' => now()->toISOString(),
        ], self::METRICS_TTL);

        $this->logger->info('🔄 Circuit Breaker State Change', [
            'service' => $service,
            'old_state' => $oldState,
            'new_state' => $newState,
        ]);
    }

    /**
     * Get comprehensive health metrics for all AI services.
     *
     * @return array<string, mixed>
     */
    public function getHealthMetrics(): array
    {
        $operations = ['text_analysis', 'product_classification', 'recommendations', 'image_analysis'];
        $metrics = [
            'overall_health' => $this->calculateOverallHealth(),
            'timestamp' => now()->toISOString(),
            'operations' => [],
        ];

        foreach ($operations as $operation) {
            $metrics['operations'][$operation] = $this->getOperationMetrics($operation);
        }

        $metrics['circuit_breakers'] = $this->getCircuitBreakerMetrics();
        $metrics['error_summary'] = $this->getErrorSummary();

        return $metrics;
    }

    /**
     * Get metrics for a specific operation.
     *
     * @return array<string, mixed>
     */
    public function getOperationMetrics(string $operation): array
    {
        $successCount = $this->getCounter("success:{$operation}");
        $failureCount = $this->getCounter("failure:{$operation}");
        $totalRequests = $successCount + $failureCount;

        $successRate = $totalRequests > 0 ? ($successCount / $totalRequests) * 100 : 0;
        $avgResponseTime = $this->getAverageResponseTime($operation);

        return [
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'total_requests' => $totalRequests,
            'success_rate' => round($successRate, 2),
            'average_response_time_ms' => round($avgResponseTime, 2),
            'last_success' => $this->getLastSuccess($operation),
            'last_failure' => $this->getLastFailure($operation),
            'health_status' => $this->getOperationHealthStatus($successRate, $avgResponseTime),
        ];
    }

    /**
     * Get circuit breaker metrics.
     *
     * @return array<string, mixed>
     */
    public function getCircuitBreakerMetrics(): array
    {
        $services = ['text_analysis', 'product_classification', 'recommendations', 'image_analysis'];
        $metrics = [];

        foreach ($services as $service) {
            $cacheKey = self::CACHE_PREFIX."circuit_breaker_state:{$service}";
            $state = Cache::get($cacheKey, ['state' => 'closed', 'timestamp' => null]);

            $metrics[$service] = [
                'state' => $state['state'],
                'last_change' => $state['timestamp'],
                'open_count' => $this->getCounter('circuit_breaker:open'),
                'half_open_count' => $this->getCounter('circuit_breaker:half_open'),
            ];
        }

        return $metrics;
    }

    /**
     * Get error summary by type.
     *
     * @return array<string, mixed>
     */
    public function getErrorSummary(): array
    {
        $errorTypes = [
            'network_error',
            'authentication_error',
            'rate_limit_error',
            'service_unavailable',
            'validation_error',
            'quota_error',
            'unknown_error',
        ];

        $summary = [];
        foreach ($errorTypes as $errorType) {
            $count = $this->getCounter("error_type:{$errorType}");
            if ($count > 0) {
                $summary[$errorType] = $count;
            }
        }

        return $summary;
    }

    /**
     * Reset all metrics (useful for testing or maintenance).
     */
    public function resetMetrics(): void
    {
        $pattern = self::CACHE_PREFIX.'*';

        // Note: This is a simplified reset. In production, you might want to use Redis SCAN
        // or implement a more sophisticated cleanup mechanism
        $keys = Cache::getStore()->getRedis()->keys($pattern);
        if (! empty($keys)) {
            Cache::getStore()->getRedis()->del($keys);
        }

        $this->logger->info('🔄 AI Monitoring Metrics Reset');
    }

    /**
     * Increment a counter metric.
     */
    private function incrementCounter(string $key): void
    {
        $cacheKey = self::CACHE_PREFIX."counter:{$key}";
        $current = Cache::get($cacheKey, 0);
        Cache::put($cacheKey, $current + 1, self::METRICS_TTL);
    }

    /**
     * Get counter value.
     */
    private function getCounter(string $key): int
    {
        $cacheKey = self::CACHE_PREFIX."counter:{$key}";

        return Cache::get($cacheKey, 0);
    }

    /**
     * Record response time for an operation.
     */
    private function recordResponseTime(string $operation, float $responseTime): void
    {
        $cacheKey = self::CACHE_PREFIX."response_times:{$operation}";
        $times = Cache::get($cacheKey, []);

        // Keep only last 100 response times to calculate average
        $times[] = $responseTime;
        if (\count($times) > 100) {
            $times = \array_slice($times, -100);
        }

        Cache::put($cacheKey, $times, self::METRICS_TTL);
    }

    /**
     * Get average response time for an operation.
     */
    private function getAverageResponseTime(string $operation): float
    {
        $cacheKey = self::CACHE_PREFIX."response_times:{$operation}";
        $times = Cache::get($cacheKey, []);

        if (empty($times)) {
            return 0.0;
        }

        return array_sum($times) / \count($times);
    }

    /**
     * Update last success timestamp.
     */
    private function updateLastSuccess(string $operation): void
    {
        $cacheKey = self::CACHE_PREFIX."last_success:{$operation}";
        Cache::put($cacheKey, now()->toISOString(), self::METRICS_TTL);
    }

    /**
     * Update last failure information.
     */
    private function updateLastFailure(string $operation, string $errorType, string $errorMessage): void
    {
        $cacheKey = self::CACHE_PREFIX."last_failure:{$operation}";
        Cache::put($cacheKey, [
            'timestamp' => now()->toISOString(),
            'error_type' => $errorType,
            'error_message' => $errorMessage,
        ], self::METRICS_TTL);
    }

    /**
     * Get last success timestamp.
     */
    private function getLastSuccess(string $operation): ?string
    {
        $cacheKey = self::CACHE_PREFIX."last_success:{$operation}";

        return Cache::get($cacheKey);
    }

    /**
     * Get last failure information.
     *
     * @return array<string, mixed>|null
     */
    private function getLastFailure(string $operation): ?array
    {
        $cacheKey = self::CACHE_PREFIX."last_failure:{$operation}";

        return Cache::get($cacheKey);
    }

    /**
     * Calculate overall health score.
     */
    private function calculateOverallHealth(): array
    {
        $operations = ['text_analysis', 'product_classification', 'recommendations', 'image_analysis'];
        $totalScore = 0;
        $operationCount = 0;

        foreach ($operations as $operation) {
            $successCount = $this->getCounter("success:{$operation}");
            $failureCount = $this->getCounter("failure:{$operation}");
            $totalRequests = $successCount + $failureCount;

            if ($totalRequests > 0) {
                $successRate = ($successCount / $totalRequests) * 100;
                $totalScore += $successRate;
                ++$operationCount;
            }
        }

        $overallScore = $operationCount > 0 ? $totalScore / $operationCount : 100;

        return [
            'score' => round($overallScore, 2),
            'status' => $this->getHealthStatus($overallScore),
        ];
    }

    /**
     * Get health status based on success rate and response time.
     */
    private function getOperationHealthStatus(float $successRate, float $avgResponseTime): string
    {
        if ($successRate >= 95 && $avgResponseTime < 2000) {
            return 'excellent';
        }
        if ($successRate >= 90 && $avgResponseTime < 5000) {
            return 'good';
        }
        if ($successRate >= 80 && $avgResponseTime < 10000) {
            return 'fair';
        }
        if ($successRate >= 70) {
            return 'poor';
        }

        return 'critical';
    }

    /**
     * Get overall health status.
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 95) {
            return 'excellent';
        }
        if ($score >= 90) {
            return 'good';
        }
        if ($score >= 80) {
            return 'fair';
        }
        if ($score >= 70) {
            return 'poor';
        }

        return 'critical';
    }
}
