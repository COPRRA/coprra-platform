<?php

declare(strict_types=1);

namespace App\Events\AI;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an agent lifecycle state changes.
 */
class AgentLifecycleEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public readonly string $agentId;
    public readonly string $event;
    public readonly string $previousState;
    public readonly string $newState;
    public readonly array $metadata;
    public readonly \DateTimeInterface $timestamp;

    /**
     * Create a new event instance.
     *
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $agentId,
        string $event,
        string $previousState,
        string $newState,
        array $metadata = []
    ) {
        $this->agentId = $agentId;
        $this->event = $event;
        $this->previousState = $previousState;
        $this->newState = $newState;
        $this->metadata = $metadata;
        $this->timestamp = now();
    }

    /**
     * Get the event data as an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'agent_id' => $this->agentId,
            'event' => $this->event,
            'previous_state' => $this->previousState,
            'new_state' => $this->newState,
            'metadata' => $this->metadata,
            'timestamp' => $this->timestamp->toISOString(),
        ];
    }
}
