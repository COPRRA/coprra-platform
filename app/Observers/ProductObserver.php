<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function created(Product $product): void
    {
        $this->invalidateProductsIndexCache();
    }

    public function updated(Product $product): void
    {
        $this->invalidateProductsIndexCache();
    }

    public function deleted(Product $product): void
    {
        $this->invalidateProductsIndexCache();
    }

    public function restored(Product $product): void
    {
        $this->invalidateProductsIndexCache();
    }

    private function invalidateProductsIndexCache(): void
    {
        $driver = config('cache.default');

        try {
            if (in_array($driver, ['redis', 'memcached', 'database'], true)) {
                Cache::tags(['products'])->flush();
            } else {
                // Fallback when tags are unsupported: flush entire cache
                Cache::flush();
            }
        } catch (\Throwable) {
            // Silently ignore cache driver errors
        }
    }
}
