<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itCanDisplayAdminDashboard(): void
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Act
        $response = $this->get(route('admin.dashboard'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas(['stats', 'recentUsers', 'recentProducts']);
        $stats = $response->viewData('stats');
        self::assertIsArray($stats);
        self::assertArrayHasKey('users', $stats);
        self::assertArrayHasKey('products', $stats);
    }

    #[Test]
    public function itRedirectsNonAdminUsersFromDashboard(): void
    {
        // Arrange
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        // Act
        $response = $this->get(route('admin.dashboard'));

        // Assert
        $response->assertRedirect();
    }

    #[Test]
    public function itCanDisplayUsersManagementPage(): void
    {
        // Arrange
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        // Act
        $response = $this->get(route('admin.users'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('admin.users');
        $response->assertViewHas('users');
        $users = $response->viewData('users');
        self::assertInstanceOf(LengthAwarePaginator::class, $users);
    }
}
