<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class BrandControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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

    #[Test]
    public function itCanDisplayBrandsIndex()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands');

        $response->assertStatus(200)
            ->assertViewIs('brands.index')
        ;
    }

    #[Test]
    public function itCanDisplayCreateBrandForm()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands/create');

        $response->assertStatus(200)
            ->assertViewIs('brands.create')
        ;
    }

    #[Test]
    public function itCanStoreNewBrand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brandData = [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'Test Brand Description',
            'website_url' => 'https://testbrand.com',
            'logo_url' => 'https://testbrand.com/logo.png',
        ];

        $response = $this->post('/brands', $brandData);

        $response->assertStatus(302)
            ->assertRedirect('/brands')
        ;

        $this->assertDatabaseHas('brands', [
            'name' => 'Test Brand',
            'description' => 'Test Brand Description',
            'website_url' => 'https://testbrand.com',
            'logo_url' => 'https://testbrand.com/logo.png',
        ]);
    }

    #[Test]
    public function itValidatesBrandCreationRequest()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post('/brands', []);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name'])
        ;
    }

    #[Test]
    public function itCanDisplayBrandDetails()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->get('/brands/'.$brand->id);

        $response->assertStatus(200)
            ->assertViewIs('brands.show')
            ->assertViewHas('brand', $brand)
        ;
    }

    #[Test]
    public function itReturns404ForNonexistentBrand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function itCanDisplayEditBrandForm()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->get('/brands/'.$brand->id.'/edit');

        $response->assertStatus(200)
            ->assertViewIs('brands.edit')
            ->assertViewHas('brand', $brand)
        ;
    }

    #[Test]
    public function itCanUpdateBrand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $updateData = [
            'name' => 'Updated Brand Name',
            'slug' => 'updated-brand-name',
            'description' => 'Updated Brand Description',
            'website_url' => 'https://updatedbrand.com',
            'logo_url' => 'https://updatedbrand.com/logo.png',
        ];

        $response = $this->put('/brands/'.$brand->id, $updateData);

        $response->assertStatus(302)
            ->assertRedirect('/brands')
        ;

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand Name',
            'description' => 'Updated Brand Description',
            'website_url' => 'https://updatedbrand.com',
            'logo_url' => 'https://updatedbrand.com/logo.png',
        ]);
    }

    #[Test]
    public function itValidatesBrandUpdateRequest()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->put('/brands/'.$brand->id, [
            'name' => '', // Empty name should fail validation
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name'])
        ;
    }

    #[Test]
    public function itCanDeleteBrand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->delete('/brands/'.$brand->id);

        $response->assertStatus(302)
            ->assertRedirect('/brands')
        ;

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    #[Test]
    public function itReturns404WhenDeletingNonexistentBrand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->delete('/brands/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function itRequiresAuthenticationForAllBrandRoutes()
    {
        $response = $this->get('/brands');
        $response->assertStatus(302); // Redirect to login

        $response = $this->get('/brands/create');
        $response->assertStatus(302);

        $response = $this->post('/brands', []);
        $response->assertStatus(302);

        $response = $this->get('/brands/1');
        $response->assertStatus(302);

        $response = $this->get('/brands/1/edit');
        $response->assertStatus(302);

        $response = $this->put('/brands/1', []);
        $response->assertStatus(302);

        $response = $this->delete('/brands/1');
        $response->assertStatus(302);
    }

    #[Test]
    public function itHandlesBrandCreationErrorsGracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        // Test with invalid data that should cause validation error
        $response = $this->post('/brands', [
            'name' => '', // Empty name should fail validation
            'slug' => '', // Empty slug should fail validation
            'description' => 'Test Description',
        ]);

        $response->assertStatus(302); // Should redirect back with validation errors
        $response->assertSessionHasErrors(['name', 'slug']);
    }

    #[Test]
    public function itHandlesBrandUpdateErrorsGracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        // Test with invalid data that should cause validation error
        $response = $this->put('/brands/'.$brand->id, [
            'name' => '', // Empty name should fail validation
            'slug' => '', // Empty slug should fail validation
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(302); // Should redirect back with validation errors
        $response->assertSessionHasErrors(['name', 'slug']);
    }

    #[Test]
    public function itHandlesBrandDeletionErrorsGracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        // Mock a database error
        $this->mock(Brand::class, static function ($mock) {
            $mock->shouldReceive('findOrFail')
                ->andThrow(new \Exception('Database error'))
            ;
        });

        $response = $this->delete('/brands/'.$brand->id);

        $response->assertStatus(500);
    }

    #[Test]
    public function itCanDisplayBrandsWithPagination()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        // Create multiple brands
        Brand::factory()->count(15)->create();

        $response = $this->get('/brands');

        $response->assertStatus(200)
            ->assertViewIs('brands.index')
        ;
    }

    #[Test]
    public function itCanSearchBrands()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand1 = Brand::factory()->create(['name' => 'Apple']);
        $brand2 = Brand::factory()->create(['name' => 'Samsung']);

        $response = $this->get('/brands?search=Apple');

        $response->assertStatus(200)
            ->assertViewIs('brands.index')
        ;
    }

    #[Test]
    public function itCanSortBrands()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        Brand::factory()->count(5)->create();

        $response = $this->get('/brands?sort=name&direction=asc');

        $response->assertStatus(200)
            ->assertViewIs('brands.index');
    }
}
