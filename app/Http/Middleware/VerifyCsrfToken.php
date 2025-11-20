<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'ai/health/agent/*/heartbeat',
        'ai/health/agent/*/initialize',
        'ai/health/agent/*/pause',
        'ai/health/agent/*/resume',
        'ai/health/agents/recover',
        'ai/health/circuit-breaker/*/reset',
    ];
}
