<?php

declare(strict_types=1);

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Service for managing agent registration and state persistence.
 */
class AgentRegistryService
{
    private const STATE_CACHE_PREFIX = 'agent_state:';
    private const STATE_TTL = 86400; // 24 hours

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $registeredAgents = [];

    /**
     * Register an agent with the lifecycle manager.
     *
     * @param array<string, mixed> $config
     */
    public function registerAgent(string $agentId, string $agentType, array $config = []): void
    {
        $this->registeredAgents[$agentId] = [
            'type' => $agentType,
            'config' => $config,
            'status' => 'initializing',
            'registered_at' => Carbon::now()->toISOString(),
            'last_heartbeat' => Carbon::now()->toISOString(),
            'health_score' => 100,
            'error_count' => 0,
            'restart_count' => 0,
        ];

        $this->persistAgentState($agentId);
        Log::info('📝 Agent registered', ['agent_id' => $agentId, 'type' => $agentType]);
    }

    /**
     * Get agent states for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getAgentStates(): array
    {
        return $this->registeredAgents;
    }

    /**
     * Get agent state by ID.
     *
     * @return array<string, mixed>|null
     */
    public function getAgentState(string $agentId): ?array
    {
        return $this->registeredAgents[$agentId] ?? null;
    }

    /**
     * Update agent state.
     *
     * @param array<string, mixed> $state
     */
    public function updateAgentState(string $agentId, array $state): void
    {
        if (isset($this->registeredAgents[$agentId])) {
            $this->registeredAgents[$agentId] = array_merge($this->registeredAgents[$agentId], $state);
            $this->persistAgentState($agentId);
        }
    }

    /**
     * Unregister an agent.
     */
    public function unregisterAgent(string $agentId): void
    {
        unset($this->registeredAgents[$agentId]);
        Cache::forget(self::STATE_CACHE_PREFIX.$agentId);

        $stateFile = "agent_states/{$agentId}.json";
        if (Storage::disk('local')->exists($stateFile)) {
            Storage::disk('local')->delete($stateFile);
        }
    }

    /**
     * Persist agent state to storage.
     */
    public function persistAgentState(string $agentId): void
    {
        if (! isset($this->registeredAgents[$agentId])) {
            return;
        }

        $state = $this->registeredAgents[$agentId];

        Cache::put(self::STATE_CACHE_PREFIX.$agentId, $state, self::STATE_TTL);

        try {
            $stateFile = "agent_states/{$agentId}.json";
            Storage::disk('local')->put($stateFile, json_encode($state, \JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            Log::warning('⚠️ Failed to persist agent state to file', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Restore agent state from storage.
     */
    public function restoreAgentState(string $agentId): bool
    {
        $state = Cache::get(self::STATE_CACHE_PREFIX.$agentId);

        if (! $state) {
            try {
                $stateFile = "agent_states/{$agentId}.json";
                if (Storage::disk('local')->exists($stateFile)) {
                    $stateJson = Storage::disk('local')->get($stateFile);
                    $state = json_decode($stateJson, true);
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Failed to restore agent state from file', [
                    'agent_id' => $agentId,
                    'error' => $e->getMessage(),
                ]);

                return false;
            }
        }

        if ($state && $this->validateAgentState($state)) {
            $this->registeredAgents[$agentId] = array_merge(
                $this->registeredAgents[$agentId] ?? [],
                $state
            );

            Log::info('🔄 Agent state restored', ['agent_id' => $agentId]);

            return true;
        }

        return false;
    }

    /**
     * Validate agent state structure and data integrity.
     *
     * @param array<string, mixed> $state
     */
    public function validateAgentState(array $state): bool
    {
        $requiredFields = ['status', 'registered_at', 'last_heartbeat'];
        $validStatuses = ['healthy', 'paused', 'failed', 'shutting_down', 'shutdown', 'active', 'initializing'];

        foreach ($requiredFields as $field) {
            if (! isset($state[$field])) {
                Log::warning('⚠️ Invalid agent state: missing required field', [
                    'missing_field' => $field,
                ]);

                return false;
            }
        }

        if (! \in_array($state['status'], $validStatuses, true)) {
            Log::warning('⚠️ Invalid agent state: invalid status', [
                'status' => $state['status'],
                'valid_statuses' => $validStatuses,
            ]);

            return false;
        }

        try {
            Carbon::parse($state['registered_at']);
            Carbon::parse($state['last_heartbeat']);
        } catch (\Exception $e) {
            Log::warning('⚠️ Invalid agent state: invalid timestamp format', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Load all persisted agent states.
     */
    public function loadAllStates(): void
    {
        try {
            $files = Storage::disk('local')->files('agent_states');

            foreach ($files as $file) {
                if (str_ends_with($file, '.json')) {
                    $agentId = basename($file, '.json');
                    $this->restoreAgentState($agentId);
                }
            }
        } catch (\Exception $e) {
            Log::error('❌ Failed to load agent states', ['error' => $e->getMessage()]);
        }
    }
}
