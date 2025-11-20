<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            $this->tryModify('orders', 'user_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('order_items', 'order_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('order_items', 'product_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('products', 'category_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('products', 'brand_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('products', 'store_id', 'BIGINT UNSIGNED NULL');
            $this->tryModify('products', 'currency_id', 'BIGINT UNSIGNED NULL');

            $this->tryModify('reviews', 'product_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('reviews', 'user_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('wishlists', 'product_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('wishlists', 'user_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('price_alerts', 'product_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('price_alerts', 'user_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('price_offers', 'product_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('payments', 'order_id', 'BIGINT UNSIGNED NOT NULL');

            $this->tryModify('product_store', 'product_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('product_store', 'store_id', 'BIGINT UNSIGNED NOT NULL');
            $this->tryModify('product_store', 'currency_id', 'BIGINT UNSIGNED NULL');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $this->tryModify('product_store', 'currency_id', 'BIGINT NULL');
            $this->tryModify('product_store', 'store_id', 'BIGINT NOT NULL');
            $this->tryModify('product_store', 'product_id', 'BIGINT NOT NULL');

            $this->tryModify('payments', 'order_id', 'BIGINT NOT NULL');

            $this->tryModify('price_offers', 'product_id', 'BIGINT NOT NULL');

            $this->tryModify('price_alerts', 'user_id', 'BIGINT NOT NULL');
            $this->tryModify('price_alerts', 'product_id', 'BIGINT NOT NULL');

            $this->tryModify('wishlists', 'user_id', 'BIGINT NOT NULL');
            $this->tryModify('wishlists', 'product_id', 'BIGINT NOT NULL');

            $this->tryModify('reviews', 'user_id', 'BIGINT NOT NULL');
            $this->tryModify('reviews', 'product_id', 'BIGINT NOT NULL');

            $this->tryModify('products', 'currency_id', 'BIGINT NULL');
            $this->tryModify('products', 'store_id', 'BIGINT NULL');
            $this->tryModify('products', 'brand_id', 'BIGINT NOT NULL');
            $this->tryModify('products', 'category_id', 'BIGINT NOT NULL');

            $this->tryModify('order_items', 'product_id', 'BIGINT NOT NULL');
            $this->tryModify('order_items', 'order_id', 'BIGINT NOT NULL');

            $this->tryModify('orders', 'user_id', 'BIGINT NOT NULL');
        });
    }

    private function tryModify(string $table, string $column, string $definition): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        $sql = "ALTER TABLE `{$table}` MODIFY `{$column}` {$definition}";

        try {
            DB::statement($sql);
        } catch (Throwable $e) {
            Log::warning('FK column type alignment failed', [
                'table' => $table,
                'column' => $column,
                'definition' => $definition,
                'error' => $e->getMessage(),
            ]);
        }
    }
};
