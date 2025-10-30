<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ProductRepository;
use App\Services\CacheService;
use App\Services\Contracts\CacheServiceContract;
use App\Services\PriceSearchService;
use App\Services\ProductService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Dusk\DuskServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(PriceSearchService::class, static function (): PriceSearchService {
            return new PriceSearchService();
        });

        // Register ProductService and its dependencies
        $this->app->singleton(CacheService::class);
        // Bind CacheServiceContract to concrete CacheService
        $this->app->singleton(CacheServiceContract::class, CacheService::class);
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(ProductService::class, static function (Application $app): ProductService {
            $repository = $app->make(ProductRepository::class);
            if (! $repository instanceof ProductRepository) {
                throw new \RuntimeException('Failed to resolve ProductRepository');
            }
            $cache = $app->make(CacheServiceContract::class);
            if (! $cache instanceof CacheServiceContract) {
                throw new \RuntimeException('Failed to resolve CacheServiceContract');
            }

            return new ProductService($repository, $cache);
        });

        if (true === $this->app->environment('local', 'testing') && class_exists(DuskServiceProvider::class)) {
            $this->app->register(DuskServiceProvider::class);
        }
    }
}
