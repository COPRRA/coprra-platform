<?php

declare(strict_types=1);

namespace App\Listeners\AI;

use App\Events\AI\AgentLifecycleEvent;
use App\Services\AI\Services\AgentLifecycleService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for agent lifecycle events.
 */
class AgentLifecycleListener implements ShouldQueue
{
    use InteractsWithQueue;

    private readonly AgentLifecycleService $lifecycleService;

    /**
     * Create the event listener.
     */
    public function __construct(AgentLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
    }

    /**
     * Handle the event.
     */
    public function handle(AgentLifecycleEvent $event): void
    {
        try {
            Log::info('🔄 Agent lifecycle event triggered', [
                'agent_id' => $event->agentId,
                'event' => $event->event,
                'previous_state' => $event->previousState,
                'new_state' => $event->newState,
                'metadata' => $event->metadata,
            ]);

            // Execute appropriate lifecycle hooks based on event type
            match ($event->event) {
                'initialized' => $this->handleInitialized($event),
                'paused' => $this->handlePaused($event),
                'resumed' => $this->handleResumed($event),
                'failed' => $this->handleFailed($event),
                'recovered' => $this->handleRecovered($event),
                'shutdown_initiated' => $this->handleShutdownInitiated($event),
                'shutdown_completed' => $this->handleShutdownCompleted($event),
                'heartbeat_missed' => $this->handleHeartbeatMissed($event),
                'state_corrupted' => $this->handleStateCorrupted($event),
                default => Log::warning('⚠️ Unknown lifecycle event', [
                    'event' => $event->event,
                    'agent_id' => $event->agentId,
                ]),
            };
        } catch (\Exception $e) {
            Log::error('❌ Failed to handle lifecycle event', [
                'agent_id' => $event->agentId,
                'event' => $event->event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle agent initialization event.
     */
    private function handleInitialized(AgentLifecycleEvent $event): void
    {
        Log::info('✅ Agent initialized successfully', [
            'agent_id' => $event->agentId,
            'metadata' => $event->metadata,
        ]);

        // Execute post-initialization hooks
        $this->executeHook('post_initialization', $event);

        // Start health monitoring
        $this->lifecycleService->recordHeartbeat($event->agentId, [
            'initialization_completed' => true,
            'initialization_time' => $event->metadata['initialization_time'] ?? null,
        ]);
    }

    /**
     * Handle agent pause event.
     */
    private function handlePaused(AgentLifecycleEvent $event): void
    {
        Log::info('⏸️ Agent paused', [
            'agent_id' => $event->agentId,
            'reason' => $event->metadata['reason'] ?? 'manual',
        ]);

        // Execute pause hooks
        $this->executeHook('on_pause', $event);

        // Save current state before pausing
        $this->lifecycleService->persistAgentState($event->agentId, [
            'paused_at' => now()->toISOString(),
            'pause_reason' => $event->metadata['reason'] ?? 'manual',
            'state_before_pause' => $event->previousState,
        ]);
    }

    /**
     * Handle agent resume event.
     */
    private function handleResumed(AgentLifecycleEvent $event): void
    {
        Log::info('▶️ Agent resumed', [
            'agent_id' => $event->agentId,
            'paused_duration' => $event->metadata['paused_duration'] ?? null,
        ]);

        // Execute resume hooks
        $this->executeHook('on_resume', $event);

        // Record resume metrics
        $this->lifecycleService->recordHeartbeat($event->agentId, [
            'resumed_at' => now()->toISOString(),
            'paused_duration' => $event->metadata['paused_duration'] ?? null,
        ]);
    }

    /**
     * Handle agent failure event.
     */
    private function handleFailed(AgentLifecycleEvent $event): void
    {
        Log::error('💥 Agent failed', [
            'agent_id' => $event->agentId,
            'error' => $event->metadata['error'] ?? 'unknown',
            'error_code' => $event->metadata['error_code'] ?? null,
        ]);

        // Execute failure hooks
        $this->executeHook('on_failure', $event);

        // Persist failure state for recovery
        $this->lifecycleService->persistAgentState($event->agentId, [
            'failed_at' => now()->toISOString(),
            'failure_reason' => $event->metadata['error'] ?? 'unknown',
            'error_code' => $event->metadata['error_code'] ?? null,
            'stack_trace' => $event->metadata['stack_trace'] ?? null,
            'recovery_attempts' => 0,
        ]);

        // Trigger automatic recovery if enabled
        if ($event->metadata['auto_recovery'] ?? true) {
            $this->scheduleRecovery($event->agentId);
        }
    }

    /**
     * Handle agent recovery event.
     */
    private function handleRecovered(AgentLifecycleEvent $event): void
    {
        Log::info('🔄 Agent recovered', [
            'agent_id' => $event->agentId,
            'recovery_attempt' => $event->metadata['recovery_attempt'] ?? 1,
            'recovery_method' => $event->metadata['recovery_method'] ?? 'automatic',
        ]);

        // Execute recovery hooks
        $this->executeHook('on_recovery', $event);

        // Update recovery statistics
        $this->lifecycleService->recordHeartbeat($event->agentId, [
            'recovered_at' => now()->toISOString(),
            'recovery_successful' => true,
            'recovery_attempt' => $event->metadata['recovery_attempt'] ?? 1,
        ]);
    }

    /**
     * Handle shutdown initiation event.
     */
    private function handleShutdownInitiated(AgentLifecycleEvent $event): void
    {
        Log::info('🛑 Agent shutdown initiated', [
            'agent_id' => $event->agentId,
            'shutdown_reason' => $event->metadata['reason'] ?? 'manual',
            'graceful' => $event->metadata['graceful'] ?? true,
        ]);

        // Execute pre-shutdown hooks
        $this->executeHook('pre_shutdown', $event);

        // Begin graceful shutdown process
        if ($event->metadata['graceful'] ?? true) {
            $this->initiateGracefulShutdown($event->agentId, $event->metadata);
        }
    }

    /**
     * Handle shutdown completion event.
     */
    private function handleShutdownCompleted(AgentLifecycleEvent $event): void
    {
        Log::info('✅ Agent shutdown completed', [
            'agent_id' => $event->agentId,
            'shutdown_duration' => $event->metadata['shutdown_duration'] ?? null,
        ]);

        // Execute post-shutdown hooks
        $this->executeHook('post_shutdown', $event);

        // Clean up agent resources
        $this->lifecycleService->cleanupAgent($event->agentId);
    }

    /**
     * Handle missed heartbeat event.
     */
    private function handleHeartbeatMissed(AgentLifecycleEvent $event): void
    {
        Log::warning('💔 Agent heartbeat missed', [
            'agent_id' => $event->agentId,
            'missed_count' => $event->metadata['missed_count'] ?? 1,
            'last_heartbeat' => $event->metadata['last_heartbeat'] ?? null,
        ]);

        // Execute heartbeat missed hooks
        $this->executeHook('on_heartbeat_missed', $event);

        // Check if agent should be marked as failed
        $missedCount = $event->metadata['missed_count'] ?? 1;
        $threshold = config('ai.agent_heartbeat_failure_threshold', 3);

        if ($missedCount >= $threshold) {
            $this->lifecycleService->markAgentAsFailed($event->agentId, 'heartbeat_timeout');
        }
    }

    /**
     * Handle state corruption event.
     */
    private function handleStateCorrupted(AgentLifecycleEvent $event): void
    {
        Log::error('🔥 Agent state corrupted', [
            'agent_id' => $event->agentId,
            'corruption_type' => $event->metadata['corruption_type'] ?? 'unknown',
            'corrupted_fields' => $event->metadata['corrupted_fields'] ?? [],
        ]);

        // Execute state corruption hooks
        $this->executeHook('on_state_corruption', $event);

        // Attempt state recovery
        $this->attemptStateRecovery($event->agentId, $event->metadata);
    }

    /**
     * Execute a lifecycle hook.
     */
    private function executeHook(string $hookName, AgentLifecycleEvent $event): void
    {
        try {
            // Log hook execution in debug mode only
            if (config('app.debug')) {
                Log::debug("🪝 Executing lifecycle hook: {$hookName}", [
                    'agent_id' => $event->agentId,
                    'hook' => $hookName,
                ]);
            }

            // Here you can implement custom hook logic
            // For now, we'll just log the hook execution
        } catch (\Exception $e) {
            Log::error("❌ Lifecycle hook failed: {$hookName}", [
                'agent_id' => $event->agentId,
                'hook' => $hookName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Schedule agent recovery.
     */
    private function scheduleRecovery(string $agentId): void
    {
        // Schedule recovery job (implement based on your queue system)
        Log::info('📅 Scheduling agent recovery', ['agent_id' => $agentId]);

        // You can dispatch a job here for delayed recovery
        // dispatch(new RecoverAgentJob($agentId))->delay(now()->addMinutes(1));
    }

    /**
     * Initiate graceful shutdown process.
     *
     * @param array<string, mixed> $metadata
     */
    private function initiateGracefulShutdown(string $agentId, array $metadata): void
    {
        Log::info('🔄 Initiating graceful shutdown', [
            'agent_id' => $agentId,
            'timeout' => $metadata['timeout'] ?? 30,
        ]);

        // Implement graceful shutdown logic
        // This could involve:
        // 1. Stopping new task acceptance
        // 2. Completing current tasks
        // 3. Saving state
        // 4. Releasing resources
    }

    /**
     * Attempt to recover corrupted agent state.
     *
     * @param array<string, mixed> $metadata
     */
    private function attemptStateRecovery(string $agentId, array $metadata): void
    {
        Log::info('🔧 Attempting state recovery', [
            'agent_id' => $agentId,
            'corruption_type' => $metadata['corruption_type'] ?? 'unknown',
        ]);

        // Implement state recovery logic
        // This could involve:
        // 1. Loading backup state
        // 2. Reconstructing state from logs
        // 3. Resetting to default state
        // 4. Manual intervention notification
    }
}
