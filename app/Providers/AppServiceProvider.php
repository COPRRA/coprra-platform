<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Observers\CategoryObserver;
use App\Observers\ProductObserver;
use App\Observers\SitemapObserver;
use App\Repositories\ProductRepository;
use App\Services\CacheService;
use App\Services\Contracts\CacheServiceContract;
use App\Services\PriceCheckerService;
use App\Services\PriceSearchService;
use App\Services\ProductService;
use App\Services\Scrapers\ScraperManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Register RateLimiter for scrapers
        $this->app->singleton(\App\Services\Scrapers\RateLimiter::class, static function (Application $app): \App\Services\Scrapers\RateLimiter {
            return new \App\Services\Scrapers\RateLimiter(
                $app->make(\Illuminate\Contracts\Cache\Repository::class)
            );
        });

        // Register ScraperManager
        $this->app->singleton(ScraperManager::class, static function (Application $app): ScraperManager {
            return new ScraperManager(
                $app->make(\Psr\Log\LoggerInterface::class),
                $app->make(\Illuminate\Contracts\Cache\Repository::class),
                $app->make(\App\Services\Scrapers\RateLimiter::class)
            );
        });

        // Register PriceCheckerService
        $this->app->singleton(PriceCheckerService::class);

        if (true === $this->app->environment('local', 'testing') && class_exists(DuskServiceProvider::class)) {
            $this->app->register(DuskServiceProvider::class);
        }

        $this->app->singleton('request', static function (): Request {
            return Request::capture();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Product::observe(SitemapObserver::class);
        Category::observe(CategoryObserver::class);

        // Register rate limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });
    }
}
