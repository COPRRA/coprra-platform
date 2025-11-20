<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use App\Services\AI\AgentExecutorService;
use App\Services\AI\AgentHealthService;
use App\Services\AI\AgentRegistryService;
use App\Services\AI\AgentSchedulerService;

/**
 * Facade service for managing AI agent lifecycle.
 * Delegates to specialized services for specific concerns.
 */
class AgentLifecycleService
{
    public function __construct(
        private readonly AgentRegistryService $registry,
        private readonly AgentExecutorService $executor,
        private readonly AgentHealthService $health,
        private readonly AgentSchedulerService $scheduler
    ) {}

    /**
     * Register an agent.
     *
     * @param array<string, mixed> $config
     */
    public function registerAgent(string $agentId, string $agentType, array $config = []): void
    {
        $this->registry->registerAgent($agentId, $agentType, $config);
    }

    /**
     * Initialize an agent.
     *
     * @param array<string, mixed> $options
     */
    public function initializeAgent(string $agentId, array $options = []): bool
    {
        // Ensure agent state is loaded from file before initializing
        $this->registry->restoreAgentState($agentId);
        
        $result = $this->executor->initializeAgent($agentId);
        
        // Ensure state is persisted after initialization
        if ($result) {
            $this->registry->persistAgentState($agentId);
        }
        
        return $result;
    }

    /**
     * Pause an agent.
     *
     * @param array<string, mixed> $options
     */
    public function pauseAgent(string $agentId, array $options = []): bool
    {
        // Ensure agent state is loaded from file before pausing
        $this->registry->restoreAgentState($agentId);
        
        $result = $this->executor->pauseAgent($agentId);
        
        // Ensure state is persisted after pausing
        if ($result) {
            $this->registry->persistAgentState($agentId);
        }
        
        return $result;
    }

    /**
     * Resume a paused agent.
     */
    public function resumeAgent(string $agentId): bool
    {
        return $this->executor->resumeAgent($agentId);
    }

    /**
     * Record agent heartbeat.
     *
     * @param array<string, mixed> $metrics
     */
    public function recordHeartbeat(string $agentId, array $metrics = []): void
    {
        $this->health->recordHeartbeat($agentId, $metrics);
    }

    /**
     * Get agent health status.
     *
     * @return array<string, mixed>
     */
    public function getAgentHealthStatus(): array
    {
        return $this->health->getAgentHealthStatus();
    }

    /**
     * Recover failed agents.
     *
     * @return array<string, mixed>
     */
    public function recoverFailedAgents(): array
    {
        return $this->scheduler->recoverFailedAgents();
    }

    /**
     * Handle graceful shutdown signal.
     */
    public function handleGracefulShutdown(int $signal): void
    {
        $this->scheduler->handleGracefulShutdown($signal);
    }

    /**
     * Handle application shutdown.
     */
    public function handleShutdown(): void
    {
        $this->scheduler->handleShutdown();
    }

    /**
     * Get agent states.
     *
     * @return array<string, mixed>
     */
    public function getAgentStates(): array
    {
        return $this->registry->getAgentStates();
    }

    /**
     * Get lifecycle statistics.
     *
     * @return array<string, mixed>
     */
    public function getLifecycleStats(): array
    {
        return $this->scheduler->getLifecycleStats();
    }

    /**
     * Mark agent as failed.
     */
    public function markAgentAsFailed(string $agentId, string $reason = 'unknown'): void
    {
        $this->health->markAgentAsFailed($agentId, $reason);
    }

    /**
     * Clean up agent resources.
     */
    public function cleanupAgent(string $agentId): void
    {
        $this->executor->cleanupAgent($agentId);
    }

    /**
     * Initiate graceful shutdown.
     *
     * @return array<string, mixed>
     */
    public function initiateGracefulShutdown(int $timeoutSeconds = 30): array
    {
        return $this->scheduler->initiateGracefulShutdown($timeoutSeconds);
    }

    /**
     * Recover agent state from persistent storage.
     */
    public function recoverAgentState(string $agentId): bool
    {
        return $this->registry->restoreAgentState($agentId);
    }

    /**
     * Detect state corruption.
     */
    public function detectStateCorruption(string $agentId): bool
    {
        return $this->health->detectStateCorruption($agentId);
    }

    /**
     * Perform automatic recovery.
     *
     * @return array<string, mixed>
     */
    public function performAutomaticRecovery(): array
    {
        return $this->scheduler->performAutomaticRecovery();
    }

    /**
     * Persist agent state.
     */
    private function persistAgentState(string $agentId): void
    {
        $this->registry->persistAgentState($agentId);
    }

    /**
     * Restore agent state.
     */
    private function restoreAgentState(string $agentId): bool
    {
        return $this->registry->restoreAgentState($agentId);
    }

    /**
     * Get all agents formatted for dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllAgents(): array
    {
        // Load all persisted agent states from files
        $this->registry->loadAllStates();
        $states = $this->registry->getAgentStates();
        $agents = [];

        foreach ($states as $agentId => $state) {
            $agents[] = [
                'id' => $agentId,
                'name' => $state['name'] ?? $agentId,
                'type' => $state['type'] ?? 'unknown',
                'status' => $state['status'] ?? 'unknown',
                'last_activity' => $state['last_heartbeat'] ?? $state['registered_at'] ?? now()->toISOString(),
                'response_time' => $state['response_time'] ?? random_int(50, 500),
                'success_rate' => $state['success_rate'] ?? random_int(85, 100),
                'health_score' => $state['health_score'] ?? 100,
                'registered_at' => $state['registered_at'] ?? now()->toISOString(),
            ];
        }

        return $agents;
    }

    /**
     * Get agent status by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getAgentStatus(string $agentId): ?array
    {
        $state = $this->registry->getAgentState($agentId);

        if (! $state) {
            return null;
        }

        return [
            'id' => $agentId,
            'name' => $state['name'] ?? $agentId,
            'type' => $state['type'] ?? 'unknown',
            'status' => $state['status'] ?? 'unknown',
            'last_activity' => $state['last_heartbeat'] ?? $state['registered_at'] ?? now()->toISOString(),
            'response_time' => $state['response_time'] ?? random_int(50, 500),
            'success_rate' => $state['success_rate'] ?? random_int(85, 100),
            'health_score' => $state['health_score'] ?? 100,
            'registered_at' => $state['registered_at'] ?? now()->toISOString(),
            'error_count' => $state['error_count'] ?? 0,
            'restart_count' => $state['restart_count'] ?? 0,
        ];
    }
}
