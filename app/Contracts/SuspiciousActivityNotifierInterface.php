<?php

declare(strict_types=1);

namespace App\Contracts;

interface SuspiciousActivityNotifierInterface
{
    /**
     * Send notifications.
     *
     * @param array{
     *     type: string,
     *     severity: string,
     *     user_id: int,
     *
     *     details: array<string, int|string|array<string, string|int|float|bool|array|* @method static \App\Models\Brand create(array<string, string|bool|null>>,
     *     timestamp: string,
     *     ip_address?: string
     * } $activity
     */
    public function sendNotifications(iterable $activity): void;
}
