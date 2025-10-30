<?php

declare(strict_types=1);

namespace Tests\AI;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/MockAIService.php';

/**
 * @internal
 *
 * @coversNothing
 */
final class MockAIServiceTest extends TestCase
{
    public function testAnalyzeTextReturnsPositiveSentimentAndStructure(): void
    {
        $service = new MockAIService();
        $text = 'This is an excellent, wonderful product. Ø±Ø§Ø¦Ø¹ ÙˆÙ…Ù…ØªØ§Ø².';

        $result = $service->analyzeText($text, 'sentiment');

        self::assertIsArray($result);
        self::assertArrayHasKey('result', $result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertArrayHasKey('confidence', $result);

        self::assertSame('positive', $result['sentiment']);
        self::assertIsString($result['result']);
        self::assertIsFloat($result['confidence']);
        self::assertSame(0.85, $result['confidence']);
    }

    public function testClassifyProductReturnsValidKeys(): void
    {
        $service = new MockAIService();
        $description = 'Gaming laptop with high performance and great battery life';

        $result = $service->classifyProduct($description);

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertArrayHasKey('subcategory', $result);
        self::assertArrayHasKey('tags', $result);
        self::assertArrayHasKey('confidence', $result);

        self::assertIsString($result['category']);
        self::assertIsString($result['subcategory']);
        self::assertIsArray($result['tags']);
        self::assertCount(2, $result['tags']);
        self::assertSame(0.85, $result['confidence']);
    }

    public function testGenerateRecommendationsReturnsThreeWithConfidence(): void
    {
        $service = new MockAIService();
        $userPreferences = ['category' => 'Electronics', 'budget' => 1000];
        $products = [
            ['id' => '101', 'category' => 'Electronics', 'brand' => 'BrandA'],
            ['id' => '102', 'category' => 'Electronics', 'brand' => 'BrandB'],
            ['id' => '103', 'category' => 'Electronics', 'brand' => 'BrandC'],
        ];

        $result = $service->generateRecommendations($userPreferences, $products);

        self::assertIsArray($result);
        self::assertArrayHasKey('recommendations', $result);
        self::assertArrayHasKey('confidence', $result);
        self::assertArrayHasKey('count', $result);

        self::assertIsArray($result['recommendations']);
        self::assertCount(3, $result['recommendations']);
        self::assertSame(0.85, $result['confidence']);
        self::assertSame(3, $result['count']);
    }

    public function testAnalyzeImageReturnsExpectedStructure(): void
    {
        $service = new MockAIService();
        $imageUrl = 'https://example.com/image.jpg';

        $result = $service->analyzeImage($imageUrl);

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertArrayHasKey('recommendations', $result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertArrayHasKey('confidence', $result);
        self::assertArrayHasKey('description', $result);

        self::assertSame('product', $result['category']);
        self::assertSame('positive', $result['sentiment']);
        self::assertIsArray($result['recommendations']);
        self::assertIsFloat($result['confidence']);
        self::assertSame(0.80, $result['confidence']);
        self::assertIsString($result['description']);
    }
}
