<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

trait TestHelpers
{
    /**
     * Create a test user with optional attributes.
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Create and authenticate as a test user.
     */
    protected function actingAsUser(?User $user = null): self
    {
        $user = $user ?? $this->createUser();

        return $this->actingAs($user);
    }

    /**
     * Create an admin user.
     */
    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_admin' => true,
        ], $attributes));
    }

    /**
     * Create and authenticate as an admin user.
     */
    protected function actingAsAdmin(?User $admin = null): self
    {
        $admin = $admin ?? $this->createAdminUser();

        return $this->actingAs($admin);
    }

    /**
     * Create a product with optional attributes.
     */
    protected function createProduct(array $attributes = []): Product
    {
        return Product::factory()->create($attributes);
    }

    /**
     * Create a category with optional attributes.
     */
    protected function createCategory(array $attributes = []): Category
    {
        return Category::factory()->create($attributes);
    }

    /**
     * Create a brand with optional attributes.
     */
    protected function createBrand(array $attributes = []): Brand
    {
        return Brand::factory()->create($attributes);
    }

    /**
     * Create a store with optional attributes.
     */
    protected function createStore(array $attributes = []): Store
    {
        return Store::factory()->create($attributes);
    }

    /**
     * Assert that a user is authenticated.
     */
    protected function assertAuthenticated(?string $guard = null): void
    {
        $this->assertNotNull(auth($guard)->user());
    }

    /**
     * Assert that a user is not authenticated.
     */
    protected function assertGuest(?string $guard = null): void
    {
        $this->assertNull(auth($guard)->user());
    }
}
