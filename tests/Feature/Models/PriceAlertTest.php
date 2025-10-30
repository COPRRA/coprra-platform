<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class PriceAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function testItCanCreateAPriceAlert(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);

        self::assertInstanceOf(PriceAlert::class, $alert);
        self::assertSame($user->id, $alert->user_id);
        self::assertSame($product->id, $alert->product_id);
        self::assertSame(99.99, $alert->target_price);
        self::assertTrue($alert->repeat_alert);
        self::assertTrue($alert->is_active);

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = PriceAlert::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => '99.99',
            'repeat_alert' => 1,
            'is_active' => 0,
        ]);

        self::assertIsString($alert->target_price);
        self::assertSame('99.99', $alert->target_price);
        self::assertIsBool($alert->repeat_alert);
        self::assertIsBool($alert->is_active);
        self::assertTrue($alert->repeat_alert);
        self::assertFalse($alert->is_active);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(User::class, $alert->user);
        self::assertSame($user->id, $alert->user->id);
    }

    #[Test]
    public function testItBelongsToProduct(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        self::assertInstanceOf(Product::class, $alert->product);
        self::assertSame($product->id, $alert->product->id);
    }

    #[Test]
    public function testScopeActive(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => true]);
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => false]);

        $activeAlerts = PriceAlert::active()->get();

        self::assertCount(1, $activeAlerts);
        self::assertTrue($activeAlerts->first()->is_active);
    }

    #[Test]
    public function testScopeForUser(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        $product = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user1->id, 'product_id' => $product->id]);
        PriceAlert::factory()->create(['user_id' => $user2->id, 'product_id' => $product->id]);

        $user1Alerts = PriceAlert::forUser($user1->id)->get();

        self::assertCount(1, $user1Alerts);
        self::assertSame($user1->id, $user1Alerts->first()->user_id);
    }

    #[Test]
    public function testScopeForProduct(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product1->id]);
        PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product2->id]);

        $product1Alerts = PriceAlert::forProduct($product1->id)->get();

        self::assertCount(1, $product1Alerts);
        self::assertSame($product1->id, $product1Alerts->first()->product_id);
    }

    #[Test]
    public function testIsPriceTargetReached(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'target_price' => 100.00]);

        self::assertTrue($alert->isPriceTargetReached(90.00));
        self::assertTrue($alert->isPriceTargetReached(100.00));
        self::assertFalse($alert->isPriceTargetReached(110.00));
    }

    #[Test]
    public function testActivate(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => false]);

        $alert->activate();

        self::assertTrue($alert->fresh()->is_active);
    }

    #[Test]
    public function testDeactivate(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id, 'is_active' => true]);

        $alert->deactivate();

        self::assertFalse($alert->fresh()->is_active);
    }

    #[Test]
    public function testValidationPassesWithValidData(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = new PriceAlert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => 99.99,
            'repeat_alert' => true,
            'is_active' => true,
        ]);

        self::assertTrue($alert->validate());
        self::assertEmpty($alert->getErrors());
    }

    #[Test]
    public function testValidationFailsWithMissingRequiredFields(): void
    {
        $alert = new PriceAlert();

        self::assertFalse($alert->validate());
        $errors = $alert->getErrors();
        self::assertArrayHasKey('user_id', $errors);
        self::assertArrayHasKey('product_id', $errors);
        self::assertArrayHasKey('target_price', $errors);
    }

    #[Test]
    public function testValidationFailsWithInvalidTargetPrice(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();

        $alert = new PriceAlert([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'target_price' => -10.00,
        ]);

        self::assertFalse($alert->validate());
        $errors = $alert->getErrors();
        self::assertArrayHasKey('target_price', $errors);
    }

    #[Test]
    public function testSoftDeletes(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $product = Product::factory()->create();
        $alert = PriceAlert::factory()->create(['user_id' => $user->id, 'product_id' => $product->id]);

        $alert->delete();

        $this->assertSoftDeleted('price_alerts', ['id' => $alert->id]);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'target_price',
            'repeat_alert',
            'is_active',
        ];

        self::assertSame($fillable, (new PriceAlert())->getFillable());
    }

    #[Test]
    public function testFactoryCreatesValidPriceAlert(): void
    {
        $alert = PriceAlert::factory()->make();

        self::assertInstanceOf(PriceAlert::class, $alert);
        self::assertIsInt($alert->user_id);
        self::assertIsInt($alert->product_id);
        self::assertIsString($alert->target_price);
        self::assertIsBool($alert->repeat_alert);
        self::assertIsBool($alert->is_active);
    }
}
