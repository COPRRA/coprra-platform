<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Controllers\Api\DocumentationController;
use Illuminate\Http\Request;

class OverrideHealthEndpoint
{
    public function handle(Request $request, \Closure $next)
    {
        // Force JSON health response for /api/health, overriding framework health page
        if ($request->is('api/health')) {
            $controller = app(DocumentationController::class);

            return $controller->health();
        }

        return $next($request);
    }
}
