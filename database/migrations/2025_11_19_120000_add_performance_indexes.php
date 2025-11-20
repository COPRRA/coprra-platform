<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index(['is_active', 'is_featured'], 'idx_products_active_featured');
            $table->index(['is_active', 'created_at'], 'idx_products_active_created_at');
            $table->index('category_id', 'idx_products_category_id');
            $table->index('brand_id', 'idx_products_brand_id');
            $table->index('slug', 'idx_products_slug');
            $table->index('name', 'idx_products_name');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->index('slug', 'idx_categories_slug');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->index('slug', 'idx_brands_slug');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['product_id', 'is_approved'], 'idx_reviews_product_approved');
        });

        Schema::table('wishlists', function (Blueprint $table): void {
            $table->index(['user_id', 'product_id'], 'idx_wishlists_user_product');
            $table->index('created_at', 'idx_wishlists_created_at');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('role', 'idx_users_role');
            $table->index('is_blocked', 'idx_users_is_blocked');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('idx_products_active_featured');
            $table->dropIndex('idx_products_active_created_at');
            $table->dropIndex('idx_products_category_id');
            $table->dropIndex('idx_products_brand_id');
            $table->dropIndex('idx_products_slug');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex('idx_categories_slug');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->dropIndex('idx_brands_slug');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('idx_reviews_product_approved');
        });

        Schema::table('wishlists', function (Blueprint $table): void {
            $table->dropIndex('idx_wishlists_user_product');
            $table->dropIndex('idx_wishlists_created_at');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('idx_users_role');
            $table->dropIndex('idx_users_is_blocked');
        });
    }
};
