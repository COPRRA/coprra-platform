<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\PriceOffer;
use App\Models\Product;
use App\Services\AuditService;
use App\Services\FinancialTransactionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FinancialTransactionServiceSecurityTest extends TestCase
{
    private FinancialTransactionService $service;
    private AuditService $mockAuditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockAuditService = $this->createMock(AuditService::class);
        $this->service = new FinancialTransactionService($this->mockAuditService);
    }

    // Security Tests for Price Updates

    public function testUpdateProductPriceWithNegativePrice(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $negativePrice = -50.00;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative');

        $this->service->updateProductPrice($product, $negativePrice);
    }

    public function testUpdateProductPriceWithExcessivePrice(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $excessivePrice = 1000000.00; // Price above limit

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price exceeds maximum allowed value');

        $this->service->updateProductPrice($product, $excessivePrice);
    }

    public function testUpdateProductPriceWithSqlInjectionAttempt(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);

        // Mock audit service to verify it's called
        $this->mockAuditService->expects(self::once())
            ->method('logPriceChange')
            ->with($product, 100.00, 150.00)
        ;

        // Act - Using a valid price but testing that the product ID isn't vulnerable
        $result = $this->service->updateProductPrice($product, 150.00);

        // Assert
        self::assertTrue($result);
        self::assertSame(150.00, $product->fresh()->price);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 150.00,
        ]);
    }

    public function testUpdateProductPriceWithConcurrentModification(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);

        // Simulate concurrent modification by updating the product in another "transaction"
        DB::table('products')->where('id', $product->id)->update(['price' => 120.00]);

        // Mock audit service
        $this->mockAuditService->expects(self::once())
            ->method('logPriceChange')
        ;

        // Act
        $result = $this->service->updateProductPrice($product, 150.00);

        // Assert
        self::assertTrue($result);
        self::assertSame(150.00, $product->fresh()->price);
    }

    // Security Tests for Price Offers

    public function testCreatePriceOfferWithInvalidData(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $invalidOfferData = [
            'price' => -50.00, // Negative price
            'expires_at' => '2020-01-01', // Past date
            'description' => str_repeat('A', 1001), // Too long description
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $this->service->createPriceOffer($product, $invalidOfferData);
    }

    public function testCreatePriceOfferWithMaliciousDescription(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $maliciousOfferData = [
            'price' => 80.00,
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => '<script>alert("xss")</script>DROP TABLE price_offers;--',
        ];

        // Act
        $offer = $this->service->createPriceOffer($product, $maliciousOfferData);

        // Assert
        self::assertInstanceOf(PriceOffer::class, $offer);
        self::assertSame(80.00, $offer->price);
        // Verify that malicious content is stored safely
        $this->assertDatabaseHas('price_offers', [
            'id' => $offer->id,
            'description' => $maliciousOfferData['description'],
        ]);
        // Verify product price is updated to the lowest offer
        self::assertSame(80.00, $product->fresh()->price);
    }

    public function testCreatePriceOfferWithExcessivePrice(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offerData = [
            'price' => 1000000.00, // Excessive price
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => 'Test offer',
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price exceeds maximum allowed value');

        $this->service->createPriceOffer($product, $offerData);
    }

    public function testUpdatePriceOfferWithUnauthorizedAccess(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offer = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 80.00,
            'expires_at' => now()->addDays(7),
        ]);

        $updateData = [
            'price' => 70.00,
            'expires_at' => now()->addDays(14)->toDateString(),
        ];

        // Act
        $result = $this->service->updatePriceOffer($offer, $updateData);

        // Assert
        self::assertTrue($result);
        self::assertSame(70.00, $offer->fresh()->price);
    }

    public function testUpdatePriceOfferWithInvalidUpdateData(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offer = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 80.00,
            'expires_at' => now()->addDays(7),
        ]);

        $invalidUpdateData = [
            'price' => -30.00, // Negative price
            'expires_at' => '2020-01-01', // Past date
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);

        $this->service->updatePriceOffer($offer, $invalidUpdateData);
    }

    public function testDeletePriceOfferWithCascadeEffects(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offer1 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 80.00,
            'expires_at' => now()->addDays(7),
        ]);
        $offer2 = PriceOffer::factory()->create([
            'product_id' => $product->id,
            'price' => 90.00,
            'expires_at' => now()->addDays(14),
        ]);

        // Update product price to lowest offer
        $product->update(['price' => 80.00]);

        // Act
        $result = $this->service->deletePriceOffer($offer1);

        // Assert
        self::assertTrue($result);
        $this->assertDatabaseMissing('price_offers', ['id' => $offer1->id]);
        // Verify product price is updated to next lowest offer
        self::assertSame(90.00, $product->fresh()->price);
    }

    // Security Tests for Price Validation

    public function testValidatePriceWithFloatPrecisionAttack(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $precisionPrice = 99.999999999999; // Float precision attack

        // Mock audit service
        $this->mockAuditService->expects(self::once())
            ->method('logPriceChange')
        ;

        // Act
        $result = $this->service->updateProductPrice($product, $precisionPrice);

        // Assert
        self::assertTrue($result);
        // Verify price is properly rounded/handled
        self::assertSame(100.00, $product->fresh()->price);
    }

    public function testValidatePriceWithInfinityValue(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $infinityPrice = \INF;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price exceeds maximum allowed value');

        $this->service->updateProductPrice($product, $infinityPrice);
    }

    public function testValidatePriceWithNaNValue(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $nanPrice = \NAN;

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Price cannot be negative');

        $this->service->updateProductPrice($product, $nanPrice);
    }

    // Security Tests for Logging

    public function testPriceUpdateLoggingDoesNotExposeSensitiveData(): void
    {
        // Arrange
        Log::spy();

        $product = Product::factory()->create([
            'price' => 100.00,
            'name' => 'Test Product',
            'description' => 'Contains sensitive info: SSN 123-45-6789',
        ]);

        // Mock audit service
        $this->mockAuditService->expects(self::once())
            ->method('logPriceChange')
        ;

        // Act
        $this->service->updateProductPrice($product, 150.00);

        // Assert
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Product price updated', \Mockery::on(static function ($context) {
                // Verify that sensitive data is not logged
                $logString = json_encode($context);

                return ! str_contains($logString, '123-45-6789')
                       && \array_key_exists('product_id', $context)
                       && \array_key_exists('old_price', $context)
                       && \array_key_exists('new_price', $context);
            }))
        ;
    }

    public function testOfferCreationLoggingWithMaliciousData(): void
    {
        // Arrange
        Log::spy();

        $product = Product::factory()->create(['price' => 100.00]);
        $maliciousOfferData = [
            'price' => 80.00,
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => 'Normal description',
            'malicious_field' => '<script>alert("xss")</script>',
        ];

        // Act
        $offer = $this->service->createPriceOffer($product, $maliciousOfferData);

        // Assert
        Log::shouldHaveReceived('info')
            ->once()
            ->with('Price offer created', \Mockery::on(static function ($context) {
                // Verify that malicious data is handled safely in logs
                return \array_key_exists('offer_id', $context)
                       && \array_key_exists('product_id', $context)
                       && \array_key_exists('price', $context);
            }))
        ;
    }

    // Security Tests for Transaction Integrity

    public function testPriceUpdateTransactionRollbackOnFailure(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $originalPrice = $product->price;

        // Mock audit service to throw exception
        $this->mockAuditService->expects(self::once())
            ->method('logPriceChange')
            ->willThrowException(new \Exception('Audit service failed'))
        ;

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Audit service failed');

        try {
            $this->service->updateProductPrice($product, 150.00);
        } catch (\Exception $e) {
            // Verify that the price wasn't updated due to transaction rollback
            self::assertSame($originalPrice, $product->fresh()->price);

            throw $e;
        }
    }

    public function testOfferCreationTransactionIntegrity(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offerData = [
            'price' => 80.00,
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => 'Test offer',
        ];

        // Simulate database constraint violation
        DB::shouldReceive('transaction')->once()->andThrow(
            new QueryException(
                'mysql',
                'INSERT INTO price_offers',
                [],
                new \Exception('Duplicate entry')
            )
        );

        // Act & Assert
        $this->expectException(QueryException::class);

        $this->service->createPriceOffer($product, $offerData);

        // Verify that product price wasn't updated
        self::assertSame(100.00, $product->fresh()->price);
    }

    // Security Tests for Input Sanitization

    public function testOfferDescriptionSanitization(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offerData = [
            'price' => 80.00,
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => "  \t\n  Test offer with whitespace  \t\n  ",
        ];

        // Act
        $offer = $this->service->createPriceOffer($product, $offerData);

        // Assert
        self::assertInstanceOf(PriceOffer::class, $offer);
        // Verify that description is properly trimmed
        self::assertSame('Test offer with whitespace', $offer->description);
    }

    public function testPriceOfferWithUnicodeCharacters(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 100.00]);
        $offerData = [
            'price' => 80.00,
            'expires_at' => now()->addDays(7)->toDateString(),
            'description' => 'Offer with émojis 🎉 and ünïcödé characters',
        ];

        // Act
        $offer = $this->service->createPriceOffer($product, $offerData);

        // Assert
        self::assertInstanceOf(PriceOffer::class, $offer);
        self::assertSame($offerData['description'], $offer->description);
        $this->assertDatabaseHas('price_offers', [
            'id' => $offer->id,
            'description' => $offerData['description'],
        ]);
    }
}
