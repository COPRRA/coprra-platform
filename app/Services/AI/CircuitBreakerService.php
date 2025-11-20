<?php

namespace App\Services\AI;

class CircuitBreakerService
{
    public function getAllStatuses(): array
    {
        return [
            ['service' => 'database', 'state' => 'CLOSED'],
            ['service' => 'cache', 'state' => 'CLOSED'],
            ['service' => 'queue', 'state' => 'CLOSED'],
        ];
    }

    public function getStatus(string $serviceName): array
    {
        return [
            'service' => $serviceName,
            'state' => 'CLOSED',
        ];
    }

    public function reset(string $serviceName): array
    {
        return [
            'service' => $serviceName,
            'status' => 'reset',
        ];
    }
}
