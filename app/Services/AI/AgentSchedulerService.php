<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Events\AI\AgentLifecycleEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for scheduling agent operations, recovery, and statistics.
 */
class AgentSchedulerService
{
    private bool $shutdownInitiated = false;

    public function __construct(
        private readonly AgentRegistryService $registry,
        private readonly AgentExecutorService $executor,
        private readonly AgentHealthService $health
    ) {
        $this->initializeLifecycleHooks();
    }

    /**
     * Recover failed agents.
     *
     * @return array<string, mixed>
     */
    public function recoverFailedAgents(): array
    {
        $agents = $this->registry->getAgentStates();
        $recoveryResults = [];

        foreach ($agents as $agentId => $agent) {
            if ('failed' === $agent['status']) {
                Log::info('🔄 Attempting to recover failed agent', ['agent_id' => $agentId]);

                if ($this->executor->initializeAgent($agentId)) {
                    $state = $this->registry->getAgentState($agentId);
                    $restartCount = ($state['restart_count'] ?? 0) + 1;
                    $this->registry->updateAgentState($agentId, ['restart_count' => $restartCount]);
                    $recoveryResults[$agentId] = 'recovered';
                } else {
                    $recoveryResults[$agentId] = 'failed';
                }
            }
        }

        return $recoveryResults;
    }

    /**
     * Perform automatic recovery for failed agents.
     *
     * @return array<string, mixed>
     */
    public function performAutomaticRecovery(): array
    {
        $agents = $this->registry->getAgentStates();
        $failedAgents = array_filter($agents, static fn ($state) => 'failed' === $state['status']);

        Log::info('🔄 Starting automatic recovery process', [
            'failed_agent_count' => \count($failedAgents),
        ]);

        $recoveryResults = [];

        foreach ($failedAgents as $agentId => $state) {
            $recoveryResults[$agentId] = $this->attemptAgentRecovery($agentId);
        }

        return $recoveryResults;
    }

    /**
     * Get lifecycle statistics for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getLifecycleStats(): array
    {
        $cacheKey = 'ai_agent_lifecycle_stats';

        return Cache::remember($cacheKey, 300, function () {
            $agents = $this->registry->getAgentStates();
            $stats = $this->initializeStats();

            $now = Carbon::now();

            foreach ($agents as $agentId => $state) {
                $this->updateStatusDistribution($stats, $state);
                $this->updateUptimeStats($stats, $state, $now);
                $this->updateHeartbeatStats($stats, $state, $now);
            }

            $this->calculateAverages($stats, \count($agents));

            return $stats;
        });
    }

    /**
     * Initiate graceful shutdown for all agents.
     *
     * @return array<string, mixed>
     */
    public function initiateGracefulShutdown(int $timeoutSeconds = 30): array
    {
        $this->shutdownInitiated = true;
        $agents = $this->registry->getAgentStates();
        $shutdownResults = [];

        Log::info('🛑 Initiating graceful shutdown for all agents', [
            'agent_count' => \count($agents),
            'timeout' => $timeoutSeconds,
        ]);

        foreach ($agents as $agentId => $state) {
            if (\in_array($state['status'], ['healthy', 'paused', 'active'], true)) {
                try {
                    event(new AgentLifecycleEvent(
                        $agentId,
                        'shutdown_initiated',
                        $state['status'],
                        'shutting_down',
                        [
                            'reason' => 'graceful_shutdown',
                            'graceful' => true,
                            'timeout' => $timeoutSeconds,
                        ]
                    ));

                    $this->registry->updateAgentState($agentId, [
                        'status' => 'shutting_down',
                        'shutdown_initiated_at' => Carbon::now()->toISOString(),
                    ]);

                    $shutdownResults[$agentId] = 'initiated';
                } catch (\Exception $e) {
                    Log::error('❌ Failed to initiate shutdown for agent', [
                        'agent_id' => $agentId,
                        'error' => $e->getMessage(),
                    ]);
                    $shutdownResults[$agentId] = 'failed';
                }
            } else {
                $shutdownResults[$agentId] = 'skipped';
            }
        }

        $this->waitForShutdownCompletion($timeoutSeconds);

        return $shutdownResults;
    }

    /**
     * Handle graceful shutdown signal.
     */
    public function handleGracefulShutdown(int $signal): void
    {
        Log::info('🛑 Graceful shutdown initiated', ['signal' => $signal]);
        $this->shutdownInitiated = true;
        $this->shutdown();
    }

    /**
     * Handle application shutdown.
     */
    public function handleShutdown(): void
    {
        if (! $this->shutdownInitiated) {
            Log::info('🛑 Application shutdown detected');
            $this->shutdown();
        }
    }

    /**
     * Initialize lifecycle hooks and signal handlers.
     */
    private function initializeLifecycleHooks(): void
    {
        register_shutdown_function([$this, 'handleShutdown']);

        if (\function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'handleGracefulShutdown']);
            pcntl_signal(SIGINT, [$this, 'handleGracefulShutdown']);
        }

        Log::info('🔄 Agent lifecycle hooks initialized');
    }

    /**
     * Perform graceful shutdown of all agents.
     */
    private function shutdown(): void
    {
        Log::info('🔄 Starting graceful shutdown of all agents');

        $agents = $this->registry->getAgentStates();

        foreach ($agents as $agentId => $agent) {
            if ('active' === $agent['status']) {
                $this->registry->updateAgentState($agentId, [
                    'status' => 'shutting_down',
                    'shutdown_at' => Carbon::now()->toISOString(),
                ]);

                Log::info('🛑 Agent shutdown', ['agent_id' => $agentId]);
            }
        }

        $this->cleanup();

        Log::info('✅ Graceful shutdown completed');
    }

    /**
     * Cleanup resources and temporary data.
     */
    private function cleanup(): void
    {
        $agents = $this->registry->getAgentStates();

        foreach ($agents as $agentId => $agent) {
            Cache::forget("agent_state:{$agentId}");
            Cache::forget("agent_health:{$agentId}");
        }

        Log::info('🧹 Cleanup completed');
    }

    /**
     * Wait for shutdown completion with timeout.
     */
    private function waitForShutdownCompletion(int $timeoutSeconds): void
    {
        $startTime = time();
        $shutdownCompleted = [];

        while ((time() - $startTime) < $timeoutSeconds) {
            $agents = $this->registry->getAgentStates();
            $allShutdown = true;

            foreach ($agents as $agentId => $state) {
                if ('shutting_down' === $state['status'] && ! isset($shutdownCompleted[$agentId])) {
                    if (isset($state['shutdown_initiated_at'])) {
                        $shutdownTime = Carbon::parse($state['shutdown_initiated_at']);
                        if ($shutdownTime->diffInSeconds(Carbon::now()) >= 5) {
                            $this->completeAgentShutdown($agentId);
                            $shutdownCompleted[$agentId] = true;
                        } else {
                            $allShutdown = false;
                        }
                    }
                }
            }

            if ($allShutdown) {
                break;
            }

            sleep(1);
        }

        $agents = $this->registry->getAgentStates();

        foreach ($agents as $agentId => $state) {
            if ('shutting_down' === $state['status'] && ! isset($shutdownCompleted[$agentId])) {
                Log::warning('⚠️ Forcing shutdown for agent (timeout reached)', ['agent_id' => $agentId]);
                $this->completeAgentShutdown($agentId, true);
            }
        }
    }

    /**
     * Complete agent shutdown.
     */
    private function completeAgentShutdown(string $agentId, bool $forced = false): void
    {
        $state = $this->registry->getAgentState($agentId);

        if (! $state) {
            return;
        }

        $shutdownDuration = null;
        if (isset($state['shutdown_initiated_at'])) {
            $initiatedAt = Carbon::parse($state['shutdown_initiated_at']);
            $shutdownDuration = $initiatedAt->diffInSeconds(Carbon::now());
        }

        event(new AgentLifecycleEvent(
            $agentId,
            'shutdown_completed',
            'shutting_down',
            'shutdown',
            [
                'shutdown_duration' => $shutdownDuration,
                'forced' => $forced,
            ]
        ));

        Log::info($forced ? '🔨 Agent shutdown forced' : '✅ Agent shutdown completed', [
            'agent_id' => $agentId,
            'shutdown_duration' => $shutdownDuration,
            'forced' => $forced,
        ]);
    }

    /**
     * Attempt to recover a specific failed agent.
     *
     * @return array<string, mixed>
     */
    private function attemptAgentRecovery(string $agentId): array
    {
        $state = $this->registry->getAgentState($agentId);

        if (! $state) {
            return ['status' => 'skipped', 'reason' => 'agent_not_found'];
        }

        $failureCount = $state['failure_count'] ?? 0;
        $maxRetries = 3;

        if ($failureCount >= $maxRetries) {
            Log::warning('⚠️ Agent recovery skipped: max retries exceeded', [
                'agent_id' => $agentId,
                'failure_count' => $failureCount,
            ]);

            return ['status' => 'skipped', 'reason' => 'max_retries_exceeded'];
        }

        if (isset($state['failed_at'])) {
            $failedAt = Carbon::parse($state['failed_at']);
            if ($failedAt->diffInSeconds(Carbon::now()) < 60) {
                return ['status' => 'skipped', 'reason' => 'recovery_interval_not_met'];
            }
        }

        try {
            Log::info('🔄 Attempting agent recovery', ['agent_id' => $agentId]);

            $previousStatus = $state['status'];
            $recoveryCount = ($state['recovery_count'] ?? 0) + 1;

            $this->registry->updateAgentState($agentId, [
                'status' => 'healthy',
                'last_heartbeat' => Carbon::now()->toISOString(),
                'recovered_at' => Carbon::now()->toISOString(),
                'recovery_count' => $recoveryCount,
                'failed_at' => null,
                'failure_reason' => null,
            ]);

            event(new AgentLifecycleEvent(
                $agentId,
                'recovered',
                $previousStatus,
                'healthy',
                [
                    'recovery_attempt' => $recoveryCount,
                    'previous_failure_count' => $failureCount,
                ]
            ));

            Log::info('✅ Agent recovery successful', ['agent_id' => $agentId]);

            return [
                'status' => 'success',
                'recovery_count' => $recoveryCount,
                'previous_failure_count' => $failureCount,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Agent recovery failed', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Initialize statistics structure.
     *
     * @return array<string, mixed>
     */
    private function initializeStats(): array
    {
        return [
            'total_agents' => 0,
            'status_distribution' => [
                'healthy' => 0,
                'active' => 0,
                'paused' => 0,
                'failed' => 0,
                'initializing' => 0,
                'shutting_down' => 0,
            ],
            'uptime_stats' => [
                'average_uptime' => 0,
                'longest_uptime' => 0,
                'shortest_uptime' => \PHP_INT_MAX,
                'total_uptime' => 0,
            ],
            'heartbeat_stats' => [
                'agents_with_recent_heartbeat' => 0,
                'average_heartbeat_interval' => 0,
                'heartbeat_intervals' => [],
            ],
        ];
    }

    /**
     * Update status distribution stats.
     *
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $state
     */
    private function updateStatusDistribution(array &$stats, array $state): void
    {
        $status = $state['status'] ?? 'unknown';
        if (isset($stats['status_distribution'][$status])) {
            ++$stats['status_distribution'][$status];
        }
    }

    /**
     * Update uptime statistics.
     *
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $state
     */
    private function updateUptimeStats(array &$stats, array $state, Carbon $now): void
    {
        if (isset($state['started_at'])) {
            $startTime = Carbon::parse($state['started_at']);
            $uptime = $now->diffInSeconds($startTime);
            $stats['uptime_stats']['total_uptime'] += $uptime;
            $stats['uptime_stats']['longest_uptime'] = max($stats['uptime_stats']['longest_uptime'], $uptime);
            $stats['uptime_stats']['shortest_uptime'] = min($stats['uptime_stats']['shortest_uptime'], $uptime);
        }
    }

    /**
     * Update heartbeat statistics.
     *
     * @param array<string, mixed> $stats
     * @param array<string, mixed> $state
     */
    private function updateHeartbeatStats(array &$stats, array $state, Carbon $now): void
    {
        if (isset($state['last_heartbeat'])) {
            $lastHeartbeat = Carbon::parse($state['last_heartbeat']);
            $timeSinceHeartbeat = $now->diffInSeconds($lastHeartbeat);

            if ($timeSinceHeartbeat <= 300) {
                ++$stats['heartbeat_stats']['agents_with_recent_heartbeat'];
            }

            if (isset($state['previous_heartbeat'])) {
                $prevHeartbeat = Carbon::parse($state['previous_heartbeat']);
                $interval = $lastHeartbeat->diffInSeconds($prevHeartbeat);
                $stats['heartbeat_stats']['heartbeat_intervals'][] = $interval;
            }
        }
    }

    /**
     * Calculate average statistics.
     *
     * @param array<string, mixed> $stats
     */
    private function calculateAverages(array &$stats, int $totalAgents): void
    {
        if ($totalAgents > 0) {
            $stats['uptime_stats']['average_uptime'] = $stats['uptime_stats']['total_uptime'] / $totalAgents;

            if (\PHP_INT_MAX === $stats['uptime_stats']['shortest_uptime']) {
                $stats['uptime_stats']['shortest_uptime'] = 0;
            }
        }

        if (! empty($stats['heartbeat_stats']['heartbeat_intervals'])) {
            $intervals = $stats['heartbeat_stats']['heartbeat_intervals'];
            $stats['heartbeat_stats']['average_heartbeat_interval'] = array_sum($intervals) / \count($intervals);
        }

        unset($stats['heartbeat_stats']['heartbeat_intervals'], $stats['uptime_stats']['total_uptime']);
    }
}
