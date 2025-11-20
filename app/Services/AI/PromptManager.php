<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Manages AI prompt templates for consistent and maintainable prompt generation.
 */
class PromptManager
{
    /**
     * System prompts for different AI operations.
     */
    private const SYSTEM_PROMPTS = [
        'text_analysis' => 'You are a helpful assistant that analyzes text for sentiment and categorization. Provide clear, structured responses with confidence scores.',
        'product_classification' => 'You are a product classification expert. Classify products into categories and provide relevant tags. Use Arabic categories when appropriate: إلكترونيات, ملابس, أدوات منزلية, كتب, رياضة.',
        'recommendation_engine' => 'You are a recommendation engine. Analyze user preferences and suggest the best products with detailed reasoning.',
        'image_analysis' => 'You are an expert image analyst. Analyze images and provide detailed insights about content, category, and recommendations.',
    ];

    /**
     * User prompt templates for different operations.
     */
    private const USER_PROMPTS = [
        'text_sentiment' => 'Analyze the following text for sentiment analysis. Provide the sentiment (positive, negative, neutral), confidence score (0-1), categories, and keywords.

Text: {text}

Please respond in this format:
Sentiment: [sentiment]
Confidence: [score]
Categories: [category1, category2]
Keywords: [keyword1, keyword2]',

        'text_classification' => 'Analyze the following text for classification. Determine the type: {type}

Text: {text}

Please provide a structured analysis with confidence score.',

        'product_classification' => 'Classify this product into appropriate categories. Use Arabic categories when suitable.

Product Description: {description}

Please respond in this format:
Category: [main category]
Subcategory: [subcategory]
Tags: [tag1, tag2, tag3]
Confidence: [score]',

        'product_recommendations' => 'Based on the user preferences and available products, provide top recommendations.

User Preferences: {preferences}
Available Products: {products}

Please provide recommendations with:
- Product ID
- Recommendation score (0-1)
- Detailed reasoning',

        'image_analysis_default' => 'Analyze this image and provide insights about:
- Main category/type of content
- Detailed description
- Recommendations or suggestions
- Overall sentiment/impression
- Confidence in analysis

Please provide structured output.',

        'image_analysis_custom' => '{prompt}',
    ];

    /**
     * Get system prompt for a specific operation.
     */
    public function getSystemPrompt(string $operation): string
    {
        return self::SYSTEM_PROMPTS[$operation] ?? self::SYSTEM_PROMPTS['text_analysis'];
    }

    /**
     * Get user prompt template for text sentiment analysis.
     */
    public function getTextSentimentPrompt(string $text): string
    {
        return str_replace('{text}', $text, self::USER_PROMPTS['text_sentiment']);
    }

    /**
     * Get user prompt template for text classification.
     */
    public function getTextClassificationPrompt(string $text, string $type): string
    {
        return str_replace(
            ['{text}', '{type}'],
            [$text, $type],
            self::USER_PROMPTS['text_classification']
        );
    }

    /**
     * Get user prompt template for product classification.
     */
    public function getProductClassificationPrompt(string $description): string
    {
        return str_replace('{description}', $description, self::USER_PROMPTS['product_classification']);
    }

    /**
     * Get user prompt template for product recommendations.
     */
    public function getRecommendationPrompt(array $userPreferences, array $products): string
    {
        return str_replace(
            ['{preferences}', '{products}'],
            [json_encode($userPreferences), json_encode($products)],
            self::USER_PROMPTS['product_recommendations']
        );
    }

    /**
     * Get user prompt template for image analysis.
     */
    public function getImageAnalysisPrompt(?string $customPrompt = null): string
    {
        if (null !== $customPrompt) {
            return str_replace('{prompt}', $customPrompt, self::USER_PROMPTS['image_analysis_custom']);
        }

        return self::USER_PROMPTS['image_analysis_default'];
    }

    /**
     * Build complete message array for OpenAI API.
     *
     * @return array<int, array<string, string>>
     */
    public function buildMessages(string $systemOperation, string $userPrompt): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($systemOperation),
            ],
            [
                'role' => 'user',
                'content' => $userPrompt,
            ],
        ];
    }

    /**
     * Build image analysis message array for OpenAI Vision API.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildImageMessages(string $imageUrl, ?string $prompt = null): array
    {
        $userPrompt = $this->getImageAnalysisPrompt($prompt);

        return [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $userPrompt,
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imageUrl,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get all available system prompt operations.
     *
     * @return array<string>
     */
    public function getAvailableOperations(): array
    {
        return array_keys(self::SYSTEM_PROMPTS);
    }

    /**
     * Validate if an operation is supported.
     */
    public function isValidOperation(string $operation): bool
    {
        return \array_key_exists($operation, self::SYSTEM_PROMPTS);
    }

    /**
     * Get prompt template by key for custom usage.
     */
    public function getPromptTemplate(string $key): ?string
    {
        return self::USER_PROMPTS[$key] ?? null;
    }

    /**
     * Add or update a custom prompt template (for runtime customization).
     */
    public function setCustomPrompt(string $key, string $template): void
    {
        // Note: This would require making USER_PROMPTS non-const for runtime modification
        // For now, this is a placeholder for future enhancement
        throw new \RuntimeException('Custom prompt modification not yet implemented. Use configuration files for custom prompts.');
    }
}
