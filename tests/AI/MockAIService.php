<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Contracts\AIServiceInterface;

/**
 * Mock AI Service for testing purposes
 * يحاكي خدمة AI الحقيقية بدون الحاجة لـ API key.
 */
class MockAIService implements AIServiceInterface
{
    public function __construct()
    {
        // Mock constructor - no dependencies needed for testing
    }

    public function analyzeText(string $text, array $options = []): array
    {
        // محاكاة تحليل النص
        $sentiment = $this->extractSentiment($text);

        return [
            'result' => "Mock analysis for: {$text}",
            'sentiment' => $sentiment,
            'confidence' => 0.85,
            'categories' => ['عام'],
            'keywords' => ['نص', 'تحليل'],
            'fallback_used' => false,
        ];
    }

    public function classifyProduct(string $productDescription, array $options = []): array
    {
        // محاكاة تصنيف المنتج
        $categories = ['إلكترونيات', 'ملابس', 'أدوات منزلية', 'كتب', 'رياضة'];
        $subcategories = ['فرعي 1', 'فرعي 2', 'فرعي 3'];
        $tags = ['جديد', 'مميز', 'عرض خاص'];

        return [
            'category' => $categories[array_rand($categories)],
            'subcategory' => $subcategories[array_rand($subcategories)],
            'tags' => [$tags[array_rand($tags)], $tags[array_rand($tags)]],
            'confidence' => 0.85,
            'fallback_used' => false,
        ];
    }

    /**
     * @param array<string, mixed>             $userPreferences
     * @param array<int, array<string, mixed>> $products
     * @param array<string, mixed>             $options
     *
     * @return array<string, mixed>
     */
    public function generateRecommendations(array $userPreferences, array $products = [], array $options = []): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªÙˆÙ„ÙŠØ¯ Ø§Ù„ØªÙˆØµÙŠØ§Øª
        return [
            'recommendations' => [
                'Recommendation 1',
                'Recommendation 2',
                'Recommendation 3',
            ],
            'confidence' => 0.85,
            'count' => 3,
        ];
    }

    public function analyzeImage(string $imagePath, array $options = []): array
    {
        // Ù…Ø­Ø§ÙƒØ§Ø© ØªØ­Ù„ÙŠÙ„ Ø§Ù„ØµÙˆØ±
        return [
            'category' => 'product',
            'recommendations' => ['Ù…Ù†ØªØ¬ Ø°Ùˆ Ø¬ÙˆØ¯Ø© Ø¹Ø§Ù„ÙŠØ©', 'Ù…Ù†Ø§Ø³Ø¨ Ù„Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù… Ø§Ù„ÙŠÙˆÙ…ÙŠ'],
            'sentiment' => 'positive',
            'confidence' => 0.80,
            'description' => 'Mock image analysis result for testing',
        ];
    }

    public function getServiceStatus(): array
    {
        return [
            'status' => 'healthy',
            'services' => [
                'text_analysis' => 'active',
                'image_analysis' => 'active',
                'recommendations' => 'active',
            ],
            'uptime' => '99.9%',
        ];
    }

    public function resetCircuitBreaker(string $serviceName): void
    {
        // Mock implementation - no actual circuit breaker to reset
    }

    public function resetAllCircuitBreakers(): void
    {
        // Mock implementation - no actual circuit breakers to reset
    }

    public function getHealthMetrics(): array
    {
        return [
            'overall_health' => 'excellent',
            'response_time' => '150ms',
            'success_rate' => '99.5%',
            'error_rate' => '0.5%',
        ];
    }

    public function getOperationMetrics(string $operation): array
    {
        return [
            'operation' => $operation,
            'total_requests' => 1000,
            'successful_requests' => 995,
            'failed_requests' => 5,
            'average_response_time' => '120ms',
        ];
    }

    public function getCircuitBreakerMetrics(): array
    {
        return [
            'text_analysis' => ['state' => 'closed', 'failure_count' => 0],
            'image_analysis' => ['state' => 'closed', 'failure_count' => 0],
            'recommendations' => ['state' => 'closed', 'failure_count' => 0],
        ];
    }

    public function getErrorSummary(): array
    {
        return [
            'total_errors' => 5,
            'error_types' => [
                'timeout' => 2,
                'rate_limit' => 2,
                'server_error' => 1,
            ],
            'last_error' => '2024-01-01 12:00:00',
        ];
    }

    public function resetMetrics(): void
    {
        // Mock implementation - no actual metrics to reset
    }

    private function extractSentiment(string $text): string
    {
        // Arabic positive words
        $positiveWords = ['Ù…Ù…ØªØ§Ø²', 'Ø±Ø§Ø¦Ø¹', 'Ø¬ÙŠØ¯', 'Ù…ÙÙŠØ¯', 'Ù…Ø«Ø§Ù„ÙŠ', 'Ù…Ù…ØªØ§Ø²Ø©', 'Ø±Ø§Ø¦Ø¹Ø©', 'Ø¬ÙŠØ¯Ø©', 'Ù…ÙÙŠØ¯Ø©', 'Ù…Ø«Ø§Ù„ÙŠØ©'];
        // English positive words
        $positiveWords = array_merge($positiveWords, [
            'excellent', 'great', 'good', 'amazing', 'wonderful', 'fantastic', 'perfect', 'outstanding', 'superb', 'brilliant',
        ]);

        // Arabic negative words
        $negativeWords = ['Ø³ÙŠØ¡', 'Ø±Ø¯ÙŠØ¡', 'Ù…Ø´ÙƒÙ„Ø©', 'Ø®Ø·Ø£', 'ÙØ§Ø´Ù„', 'Ø³ÙŠØ¦Ø©', 'Ø±Ø¯ÙŠØ¦Ø©', 'Ù…Ø´Ø§ÙƒÙ„', 'Ø£Ø®Ø·Ø§Ø¡', 'ÙØ§Ø´Ù„Ø©'];
        // English negative words
        $negativeWords = array_merge($negativeWords, [
            'bad', 'poor', 'terrible', 'awful', 'horrible', 'disappointing', 'worst', 'useless', 'defective', 'broken',
        ]);

        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($text, strtolower($word))) {
                ++$positiveCount;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($text, strtolower($word))) {
                ++$negativeCount;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        }
        if ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }
}
