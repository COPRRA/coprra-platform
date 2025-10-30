<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Enhanced test data isolation mechanisms to ensure proper test isolation.
 */
class TestDataIsolation
{
    /**
     * Tables that should be cleaned between tests.
     */
    private static array $testTables = [
        'users',
        'products',
        'categories',
        'brands',
        'stores',
        'orders',
        'order_items',
        'cart_items',
        'user_purchases',
        'reviews',
        'wishlists',
        'price_alerts',
        'analytics_events',
        'payment_methods',
        'payments',
        'user_points',
        'password_reset_tokens',
        'personal_access_tokens',
    ];

    /**
     * Set up test data isolation for a test.
     */
    public static function setUp(): void
    {
        self::enableForeignKeyConstraints();
        self::createTestTables();
        self::truncateTestTables();
    }

    /**
     * Clean up test data after a test.
     */
    public static function tearDown(): void
    {
        self::disableForeignKeyConstraints();
        self::truncateTestTables();
        self::enableForeignKeyConstraints();
    }

    /**
     * Verify that all test tables are empty.
     */
    public static function verifyIsolation(): bool
    {
        foreach (self::$testTables as $table) {
            if (Schema::hasTable($table)) {
                $count = DB::table($table)->count();
                if ($count > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the list of tables that should be cleaned.
     */
    public static function getTestTables(): array
    {
        return self::$testTables;
    }

    /**
     * Add a table to the cleanup list.
     */
    public static function addTestTable(string $table): void
    {
        if (! \in_array($table, self::$testTables, true)) {
            self::$testTables[] = $table;
        }
    }

    /**
     * Remove a table from the cleanup list.
     */
    public static function removeTestTable(string $table): void
    {
        $key = array_search($table, self::$testTables, true);
        if (false !== $key) {
            unset(self::$testTables[$key]);
            self::$testTables = array_values(self::$testTables);
        }
    }

    /**
     * Create test tables if they don't exist.
     */
    private static function createTestTables(): void
    {
        // Users table
        if (! Schema::hasTable('users')) {
            Schema::create('users', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->boolean('is_admin')->default(false);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_blocked')->default(false);
                $table->string('role')->default('user');
                $table->timestamp('ban_expires_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Products table
        if (! Schema::hasTable('products')) {
            Schema::create('products', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->string('image')->nullable();
                $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('store_id')->nullable()->constrained()->onDelete('set null');
                $table->boolean('is_active')->default(true);
                $table->integer('stock_quantity')->default(0);
                $table->timestamps();
            });
        }

        // Cart items table
        if (! Schema::hasTable('cart_items')) {
            Schema::create('cart_items', static function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->decimal('price', 10, 2);
                $table->string('session_id')->nullable();
                $table->timestamps();
            });
        }

        // User purchases table
        if (! Schema::hasTable('user_purchases')) {
            Schema::create('user_purchases', static function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->decimal('price', 10, 2);
                $table->timestamp('purchased_at');
                $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
                $table->timestamps();
            });
        }

        // Categories table
        if (! Schema::hasTable('categories')) {
            Schema::create('categories', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Brands table
        if (! Schema::hasTable('brands')) {
            Schema::create('brands', static function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('logo')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Truncate all test tables.
     */
    private static function truncateTestTables(): void
    {
        self::disableForeignKeyConstraints();

        foreach (self::$testTables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        self::enableForeignKeyConstraints();
    }

    /**
     * Disable foreign key constraints.
     */
    private static function disableForeignKeyConstraints(): void
    {
        $driver = DB::getDriverName();

        if ('sqlite' === $driver) {
            DB::statement('PRAGMA foreign_keys = OFF');
        } elseif ('mysql' === $driver) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        } elseif ('pgsql' === $driver) {
            DB::statement('SET session_replication_role = replica');
        }
    }

    /**
     * Enable foreign key constraints.
     */
    private static function enableForeignKeyConstraints(): void
    {
        $driver = DB::getDriverName();

        if ('sqlite' === $driver) {
            DB::statement('PRAGMA foreign_keys = ON');
        } elseif ('mysql' === $driver) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        } elseif ('pgsql' === $driver) {
            DB::statement('SET session_replication_role = DEFAULT');
        }
    }
}
