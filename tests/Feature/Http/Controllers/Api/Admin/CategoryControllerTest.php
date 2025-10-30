<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testCanListCategories()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $categories = Category::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
        ;
    }

    public function testRequiresAdminAuthenticationToListCategories()
    {
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(401);
    }

    public function testRequiresAdminRoleToListCategories()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(403);
    }

    public function testCanShowSpecificCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ],
            ])
        ;
    }

    public function testReturns404ForNonexistentCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/categories/999');

        $response->assertStatus(404);
    }

    public function testCanCreateCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ];

        $response = $this->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                ],
            ])
        ;

        // Assert that the category was actually saved to the database
        $this->assertDatabaseHas('categories', [
            'name' => $categoryData['name'],
            'description' => $categoryData['description'],
        ]);
    }

    public function testValidatesCategoryCreationRequest()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->postJson('/api/admin/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
        ;
    }

    public function testRequiresAdminAuthenticationToCreateCategory()
    {
        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ];

        $response = $this->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(401);
    }

    public function testCanUpdateCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Category Description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $updateData['name'],
                    'description' => $updateData['description'],
                ],
            ])
        ;
    }

    public function testValidatesCategoryUpdateRequest()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/admin/categories/{$category->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name'])
        ;
    }

    public function testRequiresAdminAuthenticationToUpdateCategory()
    {
        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Category Description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(401);
    }

    public function testCanDeleteCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(204);
    }

    public function testReturns404WhenDeletingNonexistentCategory()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->deleteJson('/api/admin/categories/999');

        $response->assertStatus(404);
    }

    public function testRequiresAdminAuthenticationToDeleteCategory()
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(401);
    }
}
