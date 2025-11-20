<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Product;

/**
 * AI Service Interface.
 *
 * Defines the contract for AI services to enable proper dependency injection,
 * testing, and service abstraction.
 */
interface AIServiceInterface
{
    /**
     * Analyze text with AI.
     *
     * @param string               $text    The text to analyze
     * @param array<string, mixed> $options Additional options for analysis
     *
     * @return array<string, mixed> Analysis results
     *
     * @throws \Exception When analysis fails
     */
    public function analyzeText(string $text, array $options = []): array;

    /**
     * Classify a product based on its description.
     *
     * @param string               $productDescription The product description to classify
     * @param array<string, mixed> $options            Additional options for classification
     *
     * @return array<string, mixed> Classification results
     *
     * @throws \Exception When classification fails
     */
    public function classifyProduct(string $productDescription, array $options = []): array;

    /**
     * Generate recommendations based on user preferences.
     *
     * @param array<string, mixed> $userPreferences User preferences for recommendations
     * @param array<Product>       $products        Available products for recommendations
     * @param array<string, mixed> $options         Additional options for recommendations
     *
     * @return array<string, mixed> Recommendation results
     *
     * @throws \Exception When recommendation generation fails
     */
    public function generateRecommendations(array $userPreferences, array $products = [], array $options = []): array;

    /**
     * Analyze an image with AI.
     *
     * @param string               $imagePath Path to the image to analyze
     * @param array<string, mixed> $options   Additional options for analysis
     *
     * @return array<string, mixed> Analysis results
     *
     * @throws \Exception When image analysis fails
     */
    public function analyzeImage(string $imagePath, array $options = []): array;

    /**
     * Get the status of AI services.
     *
     * @return array<string, mixed> Service status information
     */
    public function getServiceStatus(): array;

    /**
     * Reset circuit breaker for a specific service.
     *
     * @param string $serviceName The name of the service to reset
     */
    public function resetCircuitBreaker(string $serviceName): void;

    /**
     * Reset all circuit breakers.
     */
    public function resetAllCircuitBreakers(): void;

    /**
     * Get comprehensive health metrics for all AI services.
     *
     * @return array<string, mixed> Health metrics
     */
    public function getHealthMetrics(): array;

    /**
     * Get metrics for a specific operation.
     *
     * @param string $operation The operation to get metrics for
     *
     * @return array<string, mixed> Operation metrics
     */
    public function getOperationMetrics(string $operation): array;

    /**
     * Get circuit breaker metrics.
     *
     * @return array<string, mixed> Circuit breaker metrics
     */
    public function getCircuitBreakerMetrics(): array;

    /**
     * Get error summary.
     *
     * @return array<string, mixed> Error summary
     */
    public function getErrorSummary(): array;

    /**
     * Reset all monitoring metrics.
     */
    public function resetMetrics(): void;
}
