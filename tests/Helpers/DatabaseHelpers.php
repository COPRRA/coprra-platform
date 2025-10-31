<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait DatabaseHelpers
{
    /**
     * Assert a record exists in the database.
     */
    protected function assertDatabaseHasRecord(string $table, array $data): void
    {
        $this->assertDatabaseHas($table, $data);
    }

    /**
     * Assert multiple records exist in the database.
     */
    protected function assertDatabaseHasRecords(string $table, array $records): void
    {
        foreach ($records as $record) {
            $this->assertDatabaseHas($table, $record);
        }
    }

    /**
     * Assert database count for a table.
     */
    protected function assertDatabaseCount(string $table, int $count): void
    {
        $actual = DB::table($table)->count();
        $this->assertEquals($count, $actual, "Expected {$count} records in {$table}, found {$actual}");
    }

    /**
     * Truncate specified tables.
     */
    protected function truncateTables(array $tables): void
    {
        Schema::disableForeignKeyConstraints();

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Get the last inserted record from a table.
     */
    protected function getLastRecord(string $table): ?object
    {
        return DB::table($table)->orderBy('id', 'desc')->first();
    }

    /**
     * Assert a soft deleted record exists.
     */
    protected function assertSoftDeleted(string $table, array $data = []): void
    {
        $query = DB::table($table)->whereNotNull('deleted_at');

        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $this->assertTrue($query->exists(), "Failed asserting that soft deleted record exists in {$table}");
    }

    /**
     * Assert a record is not soft deleted.
     */
    protected function assertNotSoftDeleted(string $table, array $data): void
    {
        $query = DB::table($table)->whereNull('deleted_at');

        foreach ($data as $key => $value) {
            $query->where($key, $value);
        }

        $this->assertTrue($query->exists(), "Failed asserting that active record exists in {$table}");
    }

    /**
     * Create multiple records using factory.
     */
    protected function createMany(string $modelClass, int $count, array $attributes = []): Collection
    {
        return $modelClass::factory()->count($count)->create($attributes);
    }

    /**
     * Seed database with test data.
     */
    protected function seedTestData(): void
    {
        // Can be overridden in test classes to seed specific test data
        $this->artisan('db:seed', ['--class' => 'TestSeeder']);
    }
}
