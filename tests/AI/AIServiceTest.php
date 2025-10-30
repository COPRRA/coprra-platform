<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive AIService Test Suite.
 *
 * Tests all AIService methods with extensive edge cases and validation.
 * Follows MAX quality standards with strict type checking and comprehensive coverage.
 *
 * @internal
 *
 * @coversNothing
 */
final class AIServiceTest extends TestCase
{
    use AITestTrait;

    private AIService $aiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aiService = $this->getAIService();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ==================== analyzeText Tests ====================

    #[Test]
    public function testAnalyzeTextReturnsValidStructure(): void
    {
        $result = $this->aiService->analyzeText('Test text');

        self::assertIsArray($result);
        self::assertArrayHasKey('result', $result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertArrayHasKey('confidence', $result);

        self::assertIsString($result['result']);
        self::assertIsString($result['sentiment']);
        self::assertIsFloat($result['confidence']);
    }

    #[Test]
    #[DataProvider('provideAnalyzeTextSentimentDetectionCases')]
    public function testAnalyzeTextSentimentDetection(string $text, string $expectedSentiment): void
    {
        $result = $this->aiService->analyzeText($text);

        self::assertArrayHasKey('sentiment', $result);
        self::assertSame($expectedSentiment, $result['sentiment']);
        self::assertContains($result['sentiment'], ['positive', 'negative', 'neutral']);
    }

    public static function provideAnalyzeTextSentimentDetectionCases(): iterable
    {
        return [
            'positive_arabic' => ['Ù…Ù†ØªØ¬ Ù…Ù…ØªØ§Ø² ÙˆØ±Ø§Ø¦Ø¹ Ø¬Ø¯Ø§Ù‹', 'positive'],
            'negative_arabic' => ['Ù…Ù†ØªØ¬ Ø³ÙŠØ¡ ÙˆØ±Ø¯ÙŠØ¡', 'negative'],
            'neutral_arabic' => ['Ù…Ù†ØªØ¬ Ø¹Ø§Ø¯ÙŠ', 'neutral'],
            'positive_english' => ['excellent product', 'positive'],
            'mixed_sentiment' => ['good but expensive', 'positive'],
        ];
    }

    #[Test]
    public function testAnalyzeTextWhitespaceOnlyReturnsNeutral(): void
    {
        $result = $this->aiService->analyzeText('   ');
        self::assertArrayHasKey('sentiment', $result);
        self::assertSame('neutral', $result['sentiment']);
    }

    #[Test]
    public function testAnalyzeTextWithEmptyString(): void
    {
        $result = $this->aiService->analyzeText('');

        self::assertIsArray($result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertSame('neutral', $result['sentiment']);
    }

    #[Test]
    public function testAnalyzeTextConfidenceScoreRange(): void
    {
        $result = $this->aiService->analyzeText('Test product review');

        self::assertArrayHasKey('confidence', $result);
        self::assertGreaterThanOrEqual(0.0, $result['confidence']);
        self::assertLessThanOrEqual(1.0, $result['confidence']);
    }

    #[Test]
    #[DataProvider('provideAnalyzeTextWithDifferentTypesCases')]
    public function testAnalyzeTextWithDifferentTypes(string $text, string $type): void
    {
        $result = $this->aiService->analyzeText($text, $type);

        self::assertIsArray($result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertIsString($result['sentiment']);
    }

    public static function provideAnalyzeTextWithDifferentTypesCases(): iterable
    {
        return [
            'sentiment_type' => ['Great product', 'sentiment'],
            'classification_type' => ['Electronics item', 'classification'],
            'summary_type' => ['Long detailed description', 'summary'],
        ];
    }

    #[Test]
    public function testAnalyzeTextWithSpecialCharacters(): void
    {
        $text = 'Amazing!!! @#$%^&*() product ðŸ˜Š';
        $result = $this->aiService->analyzeText($text);

        self::assertIsArray($result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertIsString($result['sentiment']);
    }

    #[Test]
    public function testAnalyzeTextWithVeryLongText(): void
    {
        $longText = str_repeat('This is a test sentence. ', 100);
        $result = $this->aiService->analyzeText($longText);

        self::assertIsArray($result);
        self::assertArrayHasKey('result', $result);
        self::assertNotEmpty($result['result']);
    }

    #[Test]
    #[DataProvider('provideAnalyzeTextCaseInsensitiveCases')]
    public function testAnalyzeTextCaseInsensitive(string $text): void
    {
        $result = $this->aiService->analyzeText($text);
        self::assertArrayHasKey('sentiment', $result);
        self::assertSame('positive', $result['sentiment']);
    }

    public static function provideAnalyzeTextCaseInsensitiveCases(): iterable
    {
        return [
            'capitalized' => ['Excellent product'],
            'uppercase' => ['EXCELLENT item'],
            'mixed_case' => ['ExCeLlEnT choice'],
        ];
    }

    #[Test]
    public function testAnalyzeTextMixedEqualPositiveNegativeReturnsNeutral(): void
    {
        $result = $this->aiService->analyzeText('good and bad');
        self::assertArrayHasKey('sentiment', $result);
        self::assertSame('neutral', $result['sentiment']);
    }

    // ==================== classifyProduct Tests ====================

    #[Test]
    public function testClassifyProductReturnsValidStructure(): void
    {
        $result = $this->aiService->classifyProduct('Smartphone description');

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertArrayHasKey('subcategory', $result);
        self::assertArrayHasKey('tags', $result);
        self::assertArrayHasKey('confidence', $result);

        self::assertIsString($result['category']);
        self::assertIsString($result['subcategory']);
        self::assertIsArray($result['tags']);
        self::assertIsFloat($result['confidence']);
    }

    #[Test]
    #[DataProvider('provideClassifyProductWithVariousDescriptionsCases')]
    public function testClassifyProductWithVariousDescriptions(string $description): void
    {
        $result = $this->aiService->classifyProduct($description);

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertNotEmpty($result['category']);
        self::assertGreaterThan(0, \strlen($result['category']));
    }

    public static function provideClassifyProductWithVariousDescriptionsCases(): iterable
    {
        return [
            'electronics' => ['Ù‡Ø§ØªÙ Ø¢ÙŠÙÙˆÙ† 15 Ø¨Ø±Ùˆ'],
            'clothing' => ['Ù‚Ù…ÙŠØµ Ù‚Ø·Ù†ÙŠ Ø£Ø²Ø±Ù‚'],
            'books' => ['ÙƒØªØ§Ø¨ ØªØ¹Ù„Ù… Ø§Ù„Ø¨Ø±Ù…Ø¬Ø©'],
            'sports' => ['ÙƒØ±Ø© Ù‚Ø¯Ù… Ø§Ø­ØªØ±Ø§ÙÙŠØ©'],
            'furniture' => ['ÙƒØ±Ø³ÙŠ Ù…ÙƒØªØ¨ Ù…Ø±ÙŠØ­'],
            'empty' => [''],
            'very_short' => ['A'],
            'very_long' => [str_repeat('Product description ', 50)],
        ];
    }

    #[Test]
    public function testClassifyProductConfidenceRange(): void
    {
        $result = $this->aiService->classifyProduct('Gaming laptop');

        self::assertArrayHasKey('confidence', $result);
        self::assertGreaterThanOrEqual(0.0, $result['confidence']);
        self::assertLessThanOrEqual(1.0, $result['confidence']);
    }

    #[Test]
    public function testClassifyProductTagsIsArray(): void
    {
        $result = $this->aiService->classifyProduct('Modern smartphone');

        self::assertArrayHasKey('tags', $result);
        self::assertIsArray($result['tags']);
        self::assertGreaterThanOrEqual(0, \count($result['tags']));
    }

    #[Test]
    public function testClassifyProductTagsCountAndStrings(): void
    {
        $result = $this->aiService->classifyProduct('Generic product');
        self::assertArrayHasKey('tags', $result);
        self::assertIsArray($result['tags']);
        self::assertCount(2, $result['tags']);
        foreach ($result['tags'] as $tag) {
            self::assertIsString($tag);
            self::assertNotEmpty($tag);
        }
        self::assertArrayHasKey('category', $result);
        self::assertNotEmpty($result['category']);
    }

    #[Test]
    public function testClassifyProductCategoryNotEmpty(): void
    {
        $result = $this->aiService->classifyProduct('Test product');

        self::assertNotEmpty($result['category']);
        self::assertGreaterThan(2, \strlen($result['category']));
    }

    // ==================== generateRecommendations Tests ====================

    #[Test]
    public function testGenerateRecommendationsReturnsValidStructure(): void
    {
        $userPreferences = ['category' => 'electronics'];
        $products = [
            ['id' => 1, 'name' => 'Laptop', 'category' => 'electronics'],
            ['id' => 2, 'name' => 'Phone', 'category' => 'electronics'],
        ];

        $result = $this->aiService->generateRecommendations($userPreferences, $products);

        self::assertIsArray($result);
        self::assertNotEmpty($result);
    }

    #[Test]
    public function testGenerateRecommendationsWithEmptyPreferences(): void
    {
        $result = $this->aiService->generateRecommendations([], []);

        self::assertIsArray($result);
    }

    #[Test]
    #[DataProvider('provideGenerateRecommendationsWithVariousPreferencesCases')]
    public function testGenerateRecommendationsWithVariousPreferences(array $preferences, array $products): void
    {
        $result = $this->aiService->generateRecommendations($preferences, $products);

        self::assertIsArray($result);
        self::assertNotNull($result);
    }

    public static function provideGenerateRecommendationsWithVariousPreferencesCases(): iterable
    {
        return [
            'single_category' => [
                ['category' => 'books'],
                [['id' => 1, 'name' => 'Book 1', 'category' => 'books']],
            ],
            'multiple_categories' => [
                ['categories' => ['electronics', 'clothing']],
                [
                    ['id' => 1, 'name' => 'Laptop', 'category' => 'electronics'],
                    ['id' => 2, 'name' => 'Shirt', 'category' => 'clothing'],
                ],
            ],
            'price_range' => [
                ['min_price' => 100, 'max_price' => 500],
                [['id' => 1, 'name' => 'Item', 'price' => 300]],
            ],
            'empty_products' => [
                ['category' => 'test'],
                [],
            ],
        ];
    }

    // ==================== analyzeImage Tests ====================

    #[Test]
    public function testAnalyzeImageReturnsValidStructure(): void
    {
        $imageUrl = 'https://example.com/image.jpg';
        $result = $this->aiService->analyzeImage($imageUrl);

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertArrayHasKey('recommendations', $result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertArrayHasKey('confidence', $result);
        self::assertArrayHasKey('description', $result);
    }

    #[Test]
    #[DataProvider('provideAnalyzeImageWithVariousUrlsCases')]
    public function testAnalyzeImageWithVariousUrls(string $url): void
    {
        $result = $this->aiService->analyzeImage($url);

        self::assertIsArray($result);
        self::assertArrayHasKey('category', $result);
        self::assertIsString($result['category']);
    }

    public static function provideAnalyzeImageWithVariousUrlsCases(): iterable
    {
        return [
            'jpg_image' => ['https://example.com/product.jpg'],
            'png_image' => ['https://example.com/product.png'],
            'with_params' => ['https://example.com/img.jpg?size=large'],
            'short_url' => ['http://ex.co/i.jpg'],
        ];
    }

    #[Test]
    public function testAnalyzeImageWithCustomPrompt(): void
    {
        $imageUrl = 'https://example.com/image.jpg';
        $prompt = 'Identify the product category';

        $result = $this->aiService->analyzeImage($imageUrl, $prompt);

        self::assertIsArray($result);
        self::assertArrayHasKey('description', $result);
        self::assertIsString($result['description']);
    }

    #[Test]
    public function testAnalyzeImageWithInvalidUrlReturnsStructure(): void
    {
        $result = $this->aiService->analyzeImage('not-a-url');
        self::assertArrayHasKey('category', $result);
        self::assertArrayHasKey('recommendations', $result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertArrayHasKey('confidence', $result);
        self::assertArrayHasKey('description', $result);
    }

    #[Test]
    public function testAnalyzeImageDescriptionContainsMockKeyword(): void
    {
        $result = $this->aiService->analyzeImage('https://example.com/img.jpg');
        self::assertArrayHasKey('description', $result);
        self::assertStringContainsString('Mock image analysis', $result['description']);
    }

    #[Test]
    public function testAnalyzeImageConfidenceRange(): void
    {
        $result = $this->aiService->analyzeImage('https://example.com/product.jpg');

        self::assertArrayHasKey('confidence', $result);
        self::assertGreaterThanOrEqual(0.0, $result['confidence']);
        self::assertLessThanOrEqual(1.0, $result['confidence']);
    }

    // ==================== Integration Tests ====================

    #[Test]
    public function testAllMethodsReturnArrays(): void
    {
        $analyzeText = $this->aiService->analyzeText('Test');
        $classifyProduct = $this->aiService->classifyProduct('Product');
        $generateRecs = $this->aiService->generateRecommendations([], []);
        $analyzeImage = $this->aiService->analyzeImage('https://example.com/img.jpg');

        self::assertIsArray($analyzeText);
        self::assertIsArray($classifyProduct);
        self::assertIsArray($generateRecs);
        self::assertIsArray($analyzeImage);
    }

    #[Test]
    public function testServiceConsistencyAcrossMultipleCalls(): void
    {
        $text = 'Consistent test text';

        $result1 = $this->aiService->analyzeText($text);
        $result2 = $this->aiService->analyzeText($text);

        self::assertSame($result1['sentiment'], $result2['sentiment']);
        self::assertSame($result1['confidence'], $result2['confidence']);
    }

    #[Test]
    public function testServiceHandlesUnicodeCorrectly(): void
    {
        $unicodeText = 'Ù…Ù†ØªØ¬ Ø±Ø§Ø¦Ø¹ ðŸ‘ Ù…Ø¹ emoji ÙˆØ­Ø±ÙˆÙ Ø¹Ø±Ø¨ÙŠØ©';
        $result = $this->aiService->analyzeText($unicodeText);

        self::assertIsArray($result);
        self::assertArrayHasKey('sentiment', $result);
        self::assertIsString($result['sentiment']);
    }

    #[Test]
    public function testServiceMemoryEfficiency(): void
    {
        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 10; ++$i) {
            $this->aiService->analyzeText("Test iteration {$i}");
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should not use more than 5MB for 10 simple operations
        self::assertLessThan(5 * 1024 * 1024, $memoryUsed);
    }
}
