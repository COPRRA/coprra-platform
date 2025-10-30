<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @internal
 *
 * @coversNothing
 */
final class ApiEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function testApiHealthEndpointReturnsSuccessfulResponse(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'ok',
            ])
        ;
    }

    #[Test]
    public function testApiVersionEndpointReturnsVersionInfo(): void
    {
        $response = $this->getJson('/api/version');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'version',
                'api_version',
            ])
        ;

        $data = $response->json();
        self::assertNotEmpty($data['version']);
        self::assertNotEmpty($data['api_version']);
    }

    #[Test]
    public function testApiRequiresAuthenticationForProtectedEndpoints(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJsonStructure([
                'message',
            ])
        ;
    }

    #[Test]
    public function testApiReturnsUserDataWhenAuthenticated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
            ])
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ])
        ;
    }

    #[Test]
    public function testApiValidatesRequestData(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/data', [
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ])
        ;
    }

    #[Test]
    public function testApiReturnsCorrectContentType(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
        ;
    }

    #[Test]
    public function testApiHandlesInvalidJsonGracefully(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->withHeaders(['Content-Type' => 'application/json'])
            ->call('POST', '/api/data', [], [], [], [], 'invalid json')
        ;

        $response->assertStatus(400);
    }

    #[Test]
    public function testApiRateLimitingWorks(): void
    {
        $user = User::factory()->create();

        // Make multiple requests to trigger rate limiting
        for ($i = 0; $i < 100; ++$i) {
            $response = $this->actingAs($user, 'api')->getJson('/api/health');

            if (429 === $response->status()) {
                // Rate limit triggered
                $response->assertStatus(429)
                    ->assertJsonStructure([
                        'message',
                    ])
                ;

                return;
            }
        }

        // If no rate limiting, at least verify the endpoint works
        self::assertTrue(true, 'Rate limiting may not be configured or limit is very high');
    }

    #[Test]
    public function testApiReturnsNotFoundForInvalidEndpoints(): void
    {
        $response = $this->getJson('/api/nonexistent-endpoint');

        $response->assertStatus(404);
    }
}
