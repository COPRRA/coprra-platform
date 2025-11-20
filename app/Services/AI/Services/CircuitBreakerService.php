<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

/**
 * Circuit breaker pattern implementation for AI services.
 * Prevents cascading failures by temporarily disabling failing services.
 */
class CircuitBreakerService
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    private readonly LoggerInterface $logger;
    private readonly int $failureThreshold;
    private readonly int $recoveryTimeout;
    private readonly int $successThreshold;

    public function __construct(
        ?LoggerInterface $logger = null,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 3
    ) {
        $this->logger = $logger ?? app(LoggerInterface::class);
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout; // seconds
        $this->successThreshold = $successThreshold;
    }

    /**
     * Execute a callable with circuit breaker protection.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function execute(string $serviceName, callable $operation)
    {
        $state = $this->getState($serviceName);

        if (self::STATE_OPEN === $state) {
            if ($this->shouldAttemptReset($serviceName)) {
                $this->setState($serviceName, self::STATE_HALF_OPEN);
                $this->logger->info('🔄 Circuit breaker half-open for service', ['service' => $serviceName]);
            } else {
                $this->logger->warning('⚡ Circuit breaker open - blocking request', ['service' => $serviceName]);

                throw new \Exception("Service {$serviceName} is temporarily unavailable (circuit breaker open)");
            }
        }

        try {
            $result = $operation();
            $this->onSuccess($serviceName);

            return $result;
        } catch (\Exception $e) {
            $this->onFailure($serviceName);

            throw $e;
        }
    }

    /**
     * Check if the circuit breaker allows the request.
     */
    public function isAvailable(string $serviceName): bool
    {
        $state = $this->getState($serviceName);

        if (self::STATE_OPEN === $state) {
            return $this->shouldAttemptReset($serviceName);
        }

        return true;
    }

    /**
     * Get current circuit breaker state for a service.
     */
    public function getState(string $serviceName): string
    {
        return Cache::get($this->getStateKey($serviceName), self::STATE_CLOSED);
    }

    /**
     * Get circuit breaker statistics for monitoring.
     */
    public function getStats(string $serviceName): array
    {
        return [
            'state' => $this->getState($serviceName),
            'failure_count' => $this->getFailureCount($serviceName),
            'success_count' => $this->getSuccessCount($serviceName),
            'last_failure_time' => Cache::get($this->getLastFailureKey($serviceName)),
            'failure_threshold' => $this->failureThreshold,
            'recovery_timeout' => $this->recoveryTimeout,
            'success_threshold' => $this->successThreshold,
        ];
    }

    /**
     * Manually reset circuit breaker (for administrative purposes).
     */
    public function reset(string $serviceName): void
    {
        Cache::forget($this->getStateKey($serviceName));
        Cache::forget($this->getFailureCountKey($serviceName));
        Cache::forget($this->getSuccessCountKey($serviceName));
        Cache::forget($this->getLastFailureKey($serviceName));

        $this->logger->info('🔧 Circuit breaker manually reset', ['service' => $serviceName]);
    }

    /**
     * Handle successful operation.
     */
    private function onSuccess(string $serviceName): void
    {
        $state = $this->getState($serviceName);

        if (self::STATE_HALF_OPEN === $state) {
            $successCount = $this->incrementSuccessCount($serviceName);

            if ($successCount >= $this->successThreshold) {
                $this->setState($serviceName, self::STATE_CLOSED);
                $this->resetCounters($serviceName);
                $this->logger->info('✅ Circuit breaker closed - service recovered', [
                    'service' => $serviceName,
                    'success_count' => $successCount,
                ]);
            }
        } elseif (self::STATE_CLOSED === $state) {
            // Reset failure count on successful operation
            Cache::forget($this->getFailureCountKey($serviceName));
        }
    }

    /**
     * Handle failed operation.
     */
    private function onFailure(string $serviceName): void
    {
        $failureCount = $this->incrementFailureCount($serviceName);
        Cache::put($this->getLastFailureKey($serviceName), time(), 3600);

        if ($failureCount >= $this->failureThreshold) {
            $this->setState($serviceName, self::STATE_OPEN);
            $this->logger->warning('🚨 Circuit breaker opened due to failures', [
                'service' => $serviceName,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        } else {
            $this->logger->warning('⚠️ Service failure recorded', [
                'service' => $serviceName,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
        }
    }

    /**
     * Check if we should attempt to reset the circuit breaker.
     */
    private function shouldAttemptReset(string $serviceName): bool
    {
        $lastFailureTime = Cache::get($this->getLastFailureKey($serviceName));

        if (! $lastFailureTime) {
            return true;
        }

        return (time() - $lastFailureTime) >= $this->recoveryTimeout;
    }

    /**
     * Set circuit breaker state.
     */
    private function setState(string $serviceName, string $state): void
    {
        Cache::put($this->getStateKey($serviceName), $state, 3600);
    }

    /**
     * Increment failure count.
     */
    private function incrementFailureCount(string $serviceName): int
    {
        $key = $this->getFailureCountKey($serviceName);
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600);

        return $count;
    }

    /**
     * Increment success count.
     */
    private function incrementSuccessCount(string $serviceName): int
    {
        $key = $this->getSuccessCountKey($serviceName);
        $count = Cache::get($key, 0) + 1;
        Cache::put($key, $count, 3600);

        return $count;
    }

    /**
     * Get failure count.
     */
    private function getFailureCount(string $serviceName): int
    {
        return Cache::get($this->getFailureCountKey($serviceName), 0);
    }

    /**
     * Get success count.
     */
    private function getSuccessCount(string $serviceName): int
    {
        return Cache::get($this->getSuccessCountKey($serviceName), 0);
    }

    /**
     * Reset all counters.
     */
    private function resetCounters(string $serviceName): void
    {
        Cache::forget($this->getFailureCountKey($serviceName));
        Cache::forget($this->getSuccessCountKey($serviceName));
    }

    /**
     * Generate cache keys.
     */
    private function getStateKey(string $serviceName): string
    {
        return "circuit_breaker:state:{$serviceName}";
    }

    private function getFailureCountKey(string $serviceName): string
    {
        return "circuit_breaker:failures:{$serviceName}";
    }

    private function getSuccessCountKey(string $serviceName): string
    {
        return "circuit_breaker:successes:{$serviceName}";
    }

    private function getLastFailureKey(string $serviceName): string
    {
        return "circuit_breaker:last_failure:{$serviceName}";
    }
}
