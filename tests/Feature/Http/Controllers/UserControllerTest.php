<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

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
final class UserControllerTest extends TestCase
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

    public function testCanDisplayUserProfile()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/profile');

        $response->assertStatus(200)
            ->assertViewIs('user.profile')
            ->assertViewHas('user', $user)
        ;
    }

    public function testRequiresAuthenticationToViewProfile()
    {
        $response = $this->get('/profile');

        $response->assertStatus(302)
            ->assertRedirect('/login')
        ;
    }

    public function testCanUpdateUserProfile()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
            ])
        ;
    }

    public function testValidatesProfileUpdateRequest()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put('/profile', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email'])
        ;
    }

    public function testRequiresAuthenticationToUpdateProfile()
    {
        $response = $this->put('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login')
        ;
    }

    public function testCanChangeUserPassword()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password changed successfully.',
            ])
        ;
    }

    public function testValidatesPasswordChangeRequest()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->put('/profile/password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password', 'password'])
        ;
    }

    public function testRequiresAuthenticationToChangePassword()
    {
        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }
}
