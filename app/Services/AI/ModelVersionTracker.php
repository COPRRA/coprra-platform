<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * Tracks AI model versions and their performance metrics.
 */
class ModelVersionTracker
{
    private const CACHE_PREFIX = 'ai_model_version_';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Available AI models and their versions.
     */
    private const SUPPORTED_MODELS = [
        'gpt-4' => [
            'name' => 'gpt-4',
            'version' => '2024.1',
            'capabilities' => ['text_analysis', 'reasoning', 'classification'],
            'cost_per_token' => 0.00003,
            'max_tokens' => 4096,
            'release_date' => '2024-02-01',
        ],
        'gpt-4-vision' => [
            'name' => 'gpt-4-vision',
            'version' => '2024.1',
            'capabilities' => ['image_analysis', 'text_analysis'],
            'cost_per_token' => 0.00003,
            'max_tokens' => 4096,
            'release_date' => '2024-04-09',
        ],
        'gpt-3.5-turbo' => [
            'name' => 'gpt-3.5-turbo',
            'version' => '2024.1',
            'capabilities' => ['text_analysis', 'classification'],
            'cost_per_token' => 0.000002,
            'max_tokens' => 4096,
            'release_date' => '2024-01-25',
        ],
        'claude-3' => [
            'name' => 'claude-3',
            'version' => '2024.1',
            'capabilities' => ['text_analysis', 'reasoning'],
            'cost_per_token' => 0.000025,
            'max_tokens' => 4096,
            'release_date' => '2024-03-01',
        ],
        'claude-3-vision' => [
            'name' => 'claude-3-vision',
            'version' => '2024.1',
            'capabilities' => ['image_analysis', 'text_analysis'],
            'cost_per_token' => 0.000025,
            'max_tokens' => 4096,
            'release_date' => '2024-03-01',
        ],
    ];

    /**
     * In-memory storage for metrics (in production, this would use a database).
     */
    private array $metrics = [];

    /**
     * Get current model version information.
     */
    public function getModelInfo(string $modelName): ?array
    {
        if (! isset(self::SUPPORTED_MODELS[$modelName])) {
            $this->safeLog('warning', 'Unknown model requested', ['model' => $modelName]);

            return null;
        }

        return self::SUPPORTED_MODELS[$modelName];
    }

    /**
     * Track model usage and performance.
     */
    public function trackUsage(string $modelName, string $operation, bool $success, float $responseTime, int $tokens = 0): void
    {
        if (! isset($this->metrics[$modelName])) {
            $this->metrics[$modelName] = [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'total_response_time' => 0.0,
                'average_response_time' => 0.0,
                'success_rate' => 0.0,
                'total_cost' => 0.0,
                'total_tokens' => 0,
            ];
        }

        $metrics = &$this->metrics[$modelName];
        ++$metrics['total_requests'];
        $metrics['total_response_time'] += $responseTime;
        $metrics['average_response_time'] = $metrics['total_response_time'] / $metrics['total_requests'];
        $metrics['total_tokens'] += $tokens;

        if ($success) {
            ++$metrics['successful_requests'];
        } else {
            ++$metrics['failed_requests'];
        }

        $metrics['success_rate'] = ($metrics['successful_requests'] / $metrics['total_requests']) * 100;

        // Calculate cost
        $modelInfo = $this->getModelInfo($modelName);
        if ($modelInfo && $tokens > 0) {
            $cost = $tokens * $modelInfo['cost_per_token'];
            $metrics['total_cost'] += $cost;
        }

        $this->safeLog('info', 'Model usage tracked', [
            'model' => $modelName,
            'operation' => $operation,
            'success' => $success,
            'response_time' => $responseTime,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Get usage metrics for a model.
     */
    public function getModelMetrics(string $modelName): array
    {
        if (! isset($this->metrics[$modelName])) {
            return [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time' => 0,
                'success_rate' => 0,
                'total_cost' => 0,
            ];
        }

        return $this->metrics[$modelName];
    }

    /**
     * Get recommended model for a specific task type.
     */
    public function getRecommendedModel(string $taskType): string
    {
        switch ($taskType) {
            case 'text_analysis':
            case 'classification':
                return 'gpt-4';

            case 'image_analysis':
                return 'gpt-4-vision';

            case 'reasoning':
                return 'gpt-4';

            default:
                return 'gpt-4';
        }
    }

    /**
     * Compare performance between models.
     */
    public function compareModels(array $modelNames): array
    {
        $comparison = [];

        foreach ($modelNames as $modelName) {
            $metrics = $this->getModelMetrics($modelName);
            $modelInfo = $this->getModelInfo($modelName);

            $comparison[$modelName] = [
                'performance_score' => $this->calculatePerformanceScore($modelName),
                'cost_efficiency' => $this->calculateCostEfficiency($modelName),
                'reliability' => $metrics['success_rate'],
                'speed' => $metrics['average_response_time'] > 0 ? 1000 / $metrics['average_response_time'] : 0,
            ];
        }

        return $comparison;
    }

    /**
     * Get outdated models.
     */
    public function getOutdatedModels(): array
    {
        $outdated = [];

        // For demo purposes, return some mock outdated models
        $outdated[] = [
            'name' => 'gpt-3.5-turbo',
            'current_version' => '2024.1',
            'latest_version' => '2024.2',
            'days_behind' => 30,
        ];

        return $outdated;
    }

    /**
     * Get all supported models.
     */
    public function getAllModels(): array
    {
        return self::SUPPORTED_MODELS;
    }

    /**
     * Get top performing models.
     */
    public function getTopPerformingModels(int $limit = 5): array
    {
        $models = [];

        foreach (array_keys(self::SUPPORTED_MODELS) as $modelName) {
            $models[] = [
                'name' => $modelName,
                'performance_score' => $this->calculatePerformanceScore($modelName),
            ];
        }

        // Sort by performance score descending
        usort($models, static function ($a, $b) {
            return $b['performance_score'] <=> $a['performance_score'];
        });

        return \array_slice($models, 0, $limit);
    }

    /**
     * Reset all metrics.
     */
    public function resetMetrics(): void
    {
        $this->metrics = [];
        $this->safeLog('info', 'All model metrics reset');
    }

    /**
     * Calculate performance score for a model.
     */
    private function calculatePerformanceScore(string $modelName): float
    {
        $metrics = $this->getModelMetrics($modelName);

        if (0 === $metrics['total_requests']) {
            return 0.0;
        }

        // Performance score based on success rate and response time
        $successScore = $metrics['success_rate'];
        $speedScore = $metrics['average_response_time'] > 0
            ? max(0, 100 - ($metrics['average_response_time'] / 10)) // Penalize slow responses
            : 100;

        return ($successScore + $speedScore) / 2;
    }

    /**
     * Calculate cost efficiency for a model.
     */
    private function calculateCostEfficiency(string $modelName): float
    {
        $metrics = $this->getModelMetrics($modelName);
        $modelInfo = $this->getModelInfo($modelName);

        if (0 === $metrics['total_requests'] || ! $modelInfo) {
            return 0.0;
        }

        // Cost efficiency = Success Rate / Cost per Token
        $costPerToken = $modelInfo['cost_per_token'];
        if (0.0 === $costPerToken) {
            return 100.0;
        }

        return ($metrics['success_rate'] / 100) / $costPerToken;
    }

    /**
     * Safe logging that works both in Laravel context and standalone.
     */
    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            if (class_exists('Illuminate\Support\Facades\Log') && app()->bound('log')) {
                Log::{$level}($message, $context);
            }
        } catch (\Exception $e) {
            // Silently fail if logging is not available
        }
    }
}
