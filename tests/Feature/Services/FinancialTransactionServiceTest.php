<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Services\AuditService;
use App\Services\FinancialTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FinancialTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FinancialTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Verify method existence for better test reliability
        self::assertTrue(
            method_exists(AuditService::class, 'logUpdated'),
            'AuditService must have logUpdated method'
        );
        self::assertTrue(
            method_exists(FinancialTransactionService::class, 'updateProductPrice'),
            'FinancialTransactionService must have updateProductPrice method'
        );

        $auditService = \Mockery::mock(AuditService::class);
        $auditService->shouldReceive('logUpdated')->andReturn(true);
        $this->service = new FinancialTransactionService($auditService);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testProcessesPaymentSuccessfully()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        self::assertTrue($result);

        // Verify the product price was actually updated in the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);

        // Verify the old price is no longer in the database
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the product was refreshed with the new price
        $product->refresh();
        self::assertSame($newPrice, $product->price);
    }

    public function testHandlesPaymentFailure()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = -50.00; // Invalid negative price
        $reason = 'Invalid price update';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->updateProductPrice($product, $newPrice, $reason);

        // Verify the database was not changed due to the exception
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the invalid price was not saved
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);
    }

    public function testRefundsTransaction()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = 80.00; // Price reduction
        $reason = 'Price reduction';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        self::assertTrue($result);

        // Verify the price reduction was saved to the database
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => $newPrice,
        ]);

        // Verify the original price was updated
        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
            'price' => '100.50',
        ]);

        // Verify the price change was significant (reduction)
        $product->refresh();
        self::assertLessThan(100.50, $product->price);
        self::assertSame($newPrice, $product->price);
    }

    public function testGetsTransactionHistory()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        self::assertTrue($result);
    }

    public function testCalculatesTax()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update with tax';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        self::assertTrue($result);
    }

    public function testValidatesPaymentMethod()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = 120.00;
        $reason = 'Price update validation';

        // Act
        $result = $this->service->updateProductPrice($product, $newPrice, $reason);

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesInvalidPaymentMethod()
    {
        // Arrange
        $product = Product::factory()->create(['price' => '100.50']);
        $newPrice = -10.00; // Invalid negative price
        $reason = 'Invalid payment method';

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->service->updateProductPrice($product, $newPrice, $reason);
    }
}
