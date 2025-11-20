<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class CoprraServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        // Merge COPRRA configuration
        $this->mergeConfigFrom(
            config_path('coprra.php'),
            'coprra'
        );
    }
}
