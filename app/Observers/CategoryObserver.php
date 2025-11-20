<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryObserver
{
    public function created(Category $category): void
    {
        $this->invalidateCategoriesIndexCache();
    }

    public function updated(Category $category): void
    {
        $this->invalidateCategoriesIndexCache();
    }

    public function deleted(Category $category): void
    {
        $this->invalidateCategoriesIndexCache();
    }

    public function restored(Category $category): void
    {
        $this->invalidateCategoriesIndexCache();
    }

    private function invalidateCategoriesIndexCache(): void
    {
        $driver = config('cache.default');

        try {
            if (in_array($driver, ['redis', 'memcached', 'database'], true)) {
                Cache::tags(['categories'])->flush();
            } else {
                // Fallback when tags are unsupported: flush entire cache
                Cache::flush();
            }
        } catch (\Throwable) {
            // Silently ignore cache driver errors
        }
    }
}
