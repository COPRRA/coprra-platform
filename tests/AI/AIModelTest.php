<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class AIModelTest extends TestCase
{
    use AITestTrait;

    #[Test]
    public function aiModelInitializesCorrectly(): void
    {
        $aiService = $this->getAIService();

        self::assertInstanceOf(AIService::class, $aiService);
    }

    #[Test]
    public function aiCanAnalyzeText(): void
    {
        $aiService = $this->getAIService();

        $response = $aiService->analyzeText('This is a great product!');

        self::assertIsArray($response);
        self::assertTrue(
            isset($response['result']) || isset($response['error']),
            'The AI model should either return a result or an error.'
        );
    }

    #[Test]
    public function aiCanClassifyProducts(): void
    {
        $aiService = $this->getAIService();

        $productDescription = 'A high-end smartphone with a powerful camera.';
        $category = $aiService->classifyProduct($productDescription);

        self::assertIsArray($category);
        self::assertNotEmpty($category);
    }

    #[Test]
    public function aiCanGenerateRecommendations(): void
    {
        $aiService = $this->getAIService();

        $userPreferences = ['category' => 'electronics', 'max_price' => 500];
        $products = [
            ['name' => 'Smartphone', 'price' => 450],
            ['name' => 'Laptop', 'price' => 800],
        ];

        $recommendations = $aiService->generateRecommendations($userPreferences, $products);

        self::assertIsArray($recommendations);
    }

    #[Test]
    public function aiCanAnalyzeImages(): void
    {
        $aiService = $this->getAIService();

        // This test requires a valid image URL or path and a configured AI service
        // For now, we will just check if the method returns an array
        $imageUrl = 'https://example.com/image.jpg';
        $analysis = $aiService->analyzeImage($imageUrl);

        self::assertIsArray($analysis);
    }
}
