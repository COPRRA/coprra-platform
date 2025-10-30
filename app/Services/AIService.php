<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AIServiceInterface;
use App\Models\Product;
use App\Services\AI\Services\AIImageAnalysisService;
use App\Services\AI\Services\AIMonitoringService;
use App\Services\AI\Services\AITextAnalysisService;
use App\Services\AI\Services\CircuitBreakerService;
use Illuminate\Support\Facades\Log;

/**
 * Main AI service facade with circuit breaker protection.
 */
final class AIService implements AIServiceInterface
{
    private readonly AITextAnalysisService $textAnalysisService;
    private readonly AIImageAnalysisService $imageAnalysisService;
    private readonly CircuitBreakerService $circuitBreaker;
    private readonly AIMonitoringService $monitoring;

    public function __construct(
        AITextAnalysisService $textAnalysisService,
        AIImageAnalysisService $imageAnalysisService,
        ?CircuitBreakerService $circuitBreaker = null,
        ?AIMonitoringService $monitoring = null
    ) {
        $this->textAnalysisService = $textAnalysisService;
        $this->imageAnalysisService = $imageAnalysisService;
        $this->circuitBreaker = $circuitBreaker ?? new CircuitBreakerService();
        $this->monitoring = $monitoring ?? new AIMonitoringService();
    }

    /**
     * Analyze text with circuit breaker protection.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function analyzeText(string $text, array $options = []): array
    {
        Log::info('🔍 تحليل النص بالذكاء الاصطناعي', ['text_length' => mb_strlen($text)]);
        $startTime = microtime(true);

        try {
            $result = $this->circuitBreaker->execute('ai_text_analysis', function () use ($text, $options) {
                return $this->textAnalysisService->analyzeText($text, $options);
            });

            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
            $this->monitoring->recordSuccess('text_analysis', $responseTime, [
                'text_length' => mb_strlen($text),
                'has_fallback' => $result['fallback_used'] ?? false,
            ]);

            return $result;
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordFailure('text_analysis', 'unknown_error', $e->getMessage(), [
                'text_length' => mb_strlen($text),
                'response_time_ms' => $responseTime,
            ]);

            throw $e;
        }
    }

    /**
     * Classify product with circuit breaker protection.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function classifyProduct(string $productDescription, array $options = []): array
    {
        Log::info('🏷️ تصنيف المنتج بالذكاء الاصطناعي', ['description_length' => mb_strlen($productDescription)]);
        $startTime = microtime(true);

        try {
            $result = $this->circuitBreaker->execute('ai_product_classification', function () use ($productDescription, $options) {
                return $this->textAnalysisService->classifyProduct($productDescription, $options);
            });

            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordSuccess('product_classification', $responseTime, [
                'description_length' => mb_strlen($productDescription),
                'has_fallback' => $result['fallback_used'] ?? false,
            ]);

            return $result;
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordFailure('product_classification', 'unknown_error', $e->getMessage(), [
                'description_length' => mb_strlen($productDescription),
                'response_time_ms' => $responseTime,
            ]);

            throw $e;
        }
    }

    /**
     * Generate recommendations with circuit breaker protection and enhanced processing.
     *
     * @param array<string, mixed> $userPreferences
     * @param array<Product>       $products
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function generateRecommendations(array $userPreferences, array $products = [], array $options = []): array
    {
        Log::info('💡 توليد التوصيات بالذكاء الاصطناعي', [
            'preferences_count' => \count($userPreferences),
            'products_count' => \count($products),
        ]);
        $startTime = microtime(true);

        try {
            $result = $this->circuitBreaker->execute('ai_recommendations', function () use ($userPreferences, $products, $options) {
                $result = $this->textAnalysisService->generateRecommendations($userPreferences, $options);

                // Enhanced recommendation processing
                if (! empty($products)) {
                    $rawRecommendations = $result['recommendations'] ?? [];
                    $productBasedRecommendations = [];

                    foreach ($products as $product) {
                        foreach ($rawRecommendations as $recommendation) {
                            if (str_contains(strtolower($product->name ?? ''), strtolower($recommendation))
                                || str_contains(strtolower($product->description ?? ''), strtolower($recommendation))) {
                                $productBasedRecommendations[] = [
                                    'product_id' => $product->id,
                                    'product_name' => $product->name,
                                    'recommendation' => $recommendation,
                                    'confidence' => $result['confidence'] ?? 0.80,
                                ];
                            }
                        }
                    }

                    if (! empty($productBasedRecommendations)) {
                        $result['product_recommendations'] = $productBasedRecommendations;
                        $result['recommendation_type'] = 'product_based';
                    } else {
                        $result['recommendation_type'] = 'general';
                    }
                } else {
                    $result['recommendation_type'] = 'preference_based';
                }

                // Ensure confidence is set
                if (! isset($result['confidence'])) {
                    $result['confidence'] = 0.80;
                }

                return $result;
            });

            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordSuccess('recommendations', $responseTime, [
                'preferences_count' => \count($userPreferences),
                'products_count' => \count($products),
                'recommendation_type' => $result['recommendation_type'],
                'has_fallback' => $result['fallback_used'] ?? false,
            ]);

            return $result;
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordFailure('recommendations', 'unknown_error', $e->getMessage(), [
                'preferences_count' => \count($userPreferences),
                'products_count' => \count($products),
                'response_time_ms' => $responseTime,
            ]);

            throw $e;
        }
    }

    /**
     * Analyze image with circuit breaker protection.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    public function analyzeImage(string $imagePath, array $options = []): array
    {
        Log::info('🖼️ تحليل الصورة بالذكاء الاصطناعي', ['image_path' => $imagePath]);
        $startTime = microtime(true);

        try {
            $result = $this->circuitBreaker->execute('ai_image_analysis', function () use ($imagePath, $options) {
                return $this->imageAnalysisService->analyzeImage($imagePath, $options);
            });

            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordSuccess('image_analysis', $responseTime, [
                'image_path' => basename($imagePath),
                'image_size' => file_exists($imagePath) ? filesize($imagePath) : null,
                'options_count' => \count($options),
                'has_fallback' => $result['fallback_used'] ?? false,
            ]);

            return $result;
        } catch (\Exception $e) {
            $responseTime = (microtime(true) - $startTime) * 1000;
            $this->monitoring->recordFailure('image_analysis', 'unknown_error', $e->getMessage(), [
                'image_path' => basename($imagePath),
                'options_count' => \count($options),
                'response_time_ms' => $responseTime,
            ]);

            throw $e;
        }
    }

    /**
     * Check if AI services are available.
     *
     * @return array<string, mixed>
     */
    public function getServiceStatus(): array
    {
        return [
            'text_analysis' => [
                'available' => $this->circuitBreaker->isAvailable('ai_text_analysis'),
                'stats' => $this->circuitBreaker->getStats('ai_text_analysis'),
            ],
            'product_classification' => [
                'available' => $this->circuitBreaker->isAvailable('ai_product_classification'),
                'stats' => $this->circuitBreaker->getStats('ai_product_classification'),
            ],
            'recommendations' => [
                'available' => $this->circuitBreaker->isAvailable('ai_recommendations'),
                'stats' => $this->circuitBreaker->getStats('ai_recommendations'),
            ],
            'image_analysis' => [
                'available' => $this->circuitBreaker->isAvailable('ai_image_analysis'),
                'stats' => $this->circuitBreaker->getStats('ai_image_analysis'),
            ],
        ];
    }

    /**
     * Reset circuit breaker for a specific service (administrative function).
     */
    public function resetCircuitBreaker(string $serviceName): void
    {
        $this->circuitBreaker->reset($serviceName);
        Log::info('🔧 Circuit breaker reset for service', ['service' => $serviceName]);
    }

    /**
     * Reset all circuit breakers (administrative function).
     */
    public function resetAllCircuitBreakers(): void
    {
        $services = ['ai_text_analysis', 'ai_product_classification', 'ai_recommendations', 'ai_image_analysis'];

        foreach ($services as $service) {
            $this->circuitBreaker->reset($service);
        }

        Log::info('🔧 All circuit breakers reset');
    }

    /**
     * Get comprehensive health metrics for all AI services.
     *
     * @return array<string, mixed>
     */
    public function getHealthMetrics(): array
    {
        return $this->monitoring->getHealthMetrics();
    }

    /**
     * Get metrics for a specific operation.
     *
     * @return array<string, mixed>
     */
    public function getOperationMetrics(string $operation): array
    {
        return $this->monitoring->getOperationMetrics($operation);
    }

    /**
     * Get circuit breaker metrics.
     *
     * @return array<string, mixed>
     */
    public function getCircuitBreakerMetrics(): array
    {
        return $this->monitoring->getCircuitBreakerMetrics();
    }

    /**
     * Get error summary.
     *
     * @return array<string, mixed>
     */
    public function getErrorSummary(): array
    {
        return $this->monitoring->getErrorSummary();
    }

    /**
     * Reset all monitoring metrics.
     */
    public function resetMetrics(): void
    {
        $this->monitoring->resetMetrics();
        Log::info('📊 AI service monitoring metrics have been reset');
    }
}
