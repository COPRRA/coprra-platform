<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use App\Services\AI\PromptManager;
use Illuminate\Support\Facades\Log;

/**
 * Service for AI image analysis operations.
 */
class AIImageAnalysisService
{
    private readonly AIRequestService $requestService;
    private readonly PromptManager $promptManager;

    public function __construct(AIRequestService $requestService, PromptManager $promptManager)
    {
        $this->requestService = $requestService;
        $this->promptManager = $promptManager;
    }

    /**
     * Analyze an image using AI vision capabilities.
     *
     * @param string               $imageUrl URL of the image to analyze
     * @param string               $prompt   Custom prompt for analysis (optional)
     * @param array<string, mixed> $options  Additional options for analysis
     *
     * @return array<string, mixed> Analysis results
     */
    public function analyzeImage(string $imageUrl, ?string $prompt = null, array $options = []): array
    {
        try {
            Log::info('Analyzing image', ['url' => $imageUrl, 'prompt' => $prompt]);

            $messages = $this->promptManager->buildImageMessages($imageUrl, $prompt);

            $data = [
                'model' => 'gpt-4-vision-preview',
                'messages' => $messages,
                'max_tokens' => 500,
            ];

            $response = $this->requestService->makeRequest('/chat/completions', $data);

            return $this->parseImageAnalysis($response);
        } catch (\Exception $e) {
            return [
                'category' => 'error',
                'recommendations' => [],
                'sentiment' => 'neutral',
                'confidence' => 0.0,
                'description' => 'Error analyzing image: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Parse image analysis response.
     *
     * @param array<string, mixed> $response
     *
     * @return array{
     *     category: string,
     *     recommendations: list<string>,
     *     sentiment: string,
     *     confidence: float,
     *     description: string
     * }
     */
    private function parseImageAnalysis(array $response): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        return [
            'category' => $this->extractCategory($content),
            'recommendations' => $this->extractRecommendations($content),
            'sentiment' => $this->extractSentiment($content),
            'confidence' => $this->extractConfidence($content),
            'description' => $content,
        ];
    }

    /**
     * Extract category from content.
     */
    private function extractCategory(string $content): string
    {
        if (preg_match('/category[\s:]+(.+?)(?:\n|$)/iu', $content, $matches)) {
            return trim($matches[1]);
        }

        return 'general';
    }

    /**
     * Extract recommendations from content.
     *
     * @return list<string>
     */
    private function extractRecommendations(string $content): array
    {
        $recommendations = [];
        if (preg_match_all('/recommendation[\s:]+(.+?)(?:\n|$)/i', $content, $matches)) {
            $recommendations = array_map('trim', $matches[1]);
        }

        return $recommendations;
    }

    /**
     * Extract sentiment from content.
     */
    private function extractSentiment(string $content): string
    {
        if (preg_match('/sentiment[\s:]+(\w+)/i', $content, $matches)) {
            return strtolower($matches[1]);
        }

        return 'neutral';
    }

    /**
     * Extract confidence from content.
     */
    private function extractConfidence(string $content): float
    {
        if (preg_match('/confidence[\s:]+(\d+(?:\.\d+)?)/i', $content, $matches)) {
            return (float) $matches[1];
        }

        return 0.5;
    }
}
