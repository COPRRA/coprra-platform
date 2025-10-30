<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\AIImageAnalysisService;
use App\Services\AIService;
use App\Services\AITextAnalysisService;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edge case tests for AIService covering critical failure scenarios.
 *
 * @internal
 *
 * @coversNothing
 */
final class AIServiceEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    private AIService $aiService;
    private AITextAnalysisService $mockTextAnalysisService;
    private AIImageAnalysisService $mockImageAnalysisService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockTextAnalysisService = \Mockery::mock(AITextAnalysisService::class);
        $this->mockImageAnalysisService = \Mockery::mock(AIImageAnalysisService::class);

        $this->aiService = new AIService(
            $this->mockTextAnalysisService,
            $this->mockImageAnalysisService
        );
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testAnalyzeTextWithApiTimeout(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new ConnectException(
                'Connection timeout',
                new Request('POST', 'https://api.example.com/analyze')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('timeout', strtolower($result['error']));
    }

    public function testAnalyzeTextWithRateLimitExceeded(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new RequestException(
                'Rate limit exceeded',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(429, [], '{"error": "Rate limit exceeded"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('rate limit', strtolower($result['error']));
    }

    public function testAnalyzeTextWithMalformedApiResponse(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andReturn('invalid_json_response') // Not an array
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('malformed', strtolower($result['error']));
    }

    public function testAnalyzeTextWithEmptyApiResponse(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andReturn([])
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('empty', strtolower($result['error']));
    }

    public function testAnalyzeTextWithServerError(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new ServerException(
                'Internal server error',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(500, [], '{"error": "Internal server error"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('server error', strtolower($result['error']));
    }

    public function testAnalyzeSentimentWithExtremelyLongText(): void
    {
        $longText = str_repeat('This is a very long text. ', 10000); // ~270KB text

        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with($longText)
            ->andThrow(new RequestException(
                'Request entity too large',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(413, [], '{"error": "Request entity too large"}')
            ))
        ;

        $result = $this->aiService->analyzeSentiment($longText);

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('too large', strtolower($result['error']));
    }

    public function testAnalyzeSentimentWithInvalidCharacters(): void
    {
        $invalidText = "Text with null bytes\0and control characters\x01\x02";

        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with($invalidText)
            ->andThrow(new RequestException(
                'Invalid characters in request',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(400, [], '{"error": "Invalid characters"}')
            ))
        ;

        $result = $this->aiService->analyzeSentiment($invalidText);

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('invalid', strtolower($result['error']));
    }

    public function testClassifyTextWithIncompleteResponse(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('classifyText')
            ->once()
            ->with('Test text')
            ->andReturn([
                'category' => 'electronics',
                // Missing confidence field
            ])
        ;

        $result = $this->aiService->classifyText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('incomplete', strtolower($result['error']));
    }

    public function testClassifyProductWithNegativeConfidence(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('classifyProduct')
            ->once()
            ->with('Product description')
            ->andReturn([
                'category' => 'electronics',
                'confidence' => -0.5, // Invalid negative confidence
            ])
        ;

        $result = $this->aiService->classifyProduct('Product description');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('invalid confidence', strtolower($result['error']));
    }

    public function testGenerateRecommendationsWithNetworkFailure(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('generateRecommendations')
            ->once()
            ->with(1, ['category' => 'electronics'])
            ->andThrow(new ConnectException(
                'Network unreachable',
                new Request('POST', 'https://api.example.com/recommendations')
            ))
        ;

        $result = $this->aiService->generateRecommendations(1, ['category' => 'electronics']);

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('network', strtolower($result['error']));
    }

    public function testGenerateRecommendationsWithInvalidUserId(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('generateRecommendations')
            ->once()
            ->with(-1, [])
            ->andThrow(new RequestException(
                'Invalid user ID',
                new Request('POST', 'https://api.example.com/recommendations'),
                new Response(400, [], '{"error": "Invalid user ID"}')
            ))
        ;

        $result = $this->aiService->generateRecommendations(-1, []);

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('invalid user', strtolower($result['error']));
    }

    public function testAnalyzeImageWithInvalidUrl(): void
    {
        $invalidUrl = 'not-a-valid-url';

        $this->mockImageAnalysisService
            ->shouldReceive('analyzeImage')
            ->once()
            ->with($invalidUrl, 'Analyze this image')
            ->andThrow(new RequestException(
                'Invalid URL format',
                new Request('POST', 'https://api.example.com/image-analysis'),
                new Response(400, [], '{"error": "Invalid URL format"}')
            ))
        ;

        $result = $this->aiService->analyzeImage($invalidUrl, 'Analyze this image');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('invalid url', strtolower($result['error']));
    }

    public function testAnalyzeImageWithUnsupportedFormat(): void
    {
        $imageUrl = 'https://example.com/image.bmp';

        $this->mockImageAnalysisService
            ->shouldReceive('analyzeImage')
            ->once()
            ->with($imageUrl, 'Analyze this image')
            ->andThrow(new RequestException(
                'Unsupported image format',
                new Request('POST', 'https://api.example.com/image-analysis'),
                new Response(415, [], '{"error": "Unsupported image format"}')
            ))
        ;

        $result = $this->aiService->analyzeImage($imageUrl, 'Analyze this image');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('unsupported', strtolower($result['error']));
    }

    public function testAnalyzeImageWithImageTooLarge(): void
    {
        $imageUrl = 'https://example.com/very-large-image.jpg';

        $this->mockImageAnalysisService
            ->shouldReceive('analyzeImage')
            ->once()
            ->with($imageUrl, 'Analyze this image')
            ->andThrow(new RequestException(
                'Image too large',
                new Request('POST', 'https://api.example.com/image-analysis'),
                new Response(413, [], '{"error": "Image exceeds maximum size limit"}')
            ))
        ;

        $result = $this->aiService->analyzeImage($imageUrl, 'Analyze this image');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('too large', strtolower($result['error']));
    }

    public function testExtractTextFromImageWithCorruptedImage(): void
    {
        $imageUrl = 'https://example.com/corrupted-image.jpg';

        $this->mockImageAnalysisService
            ->shouldReceive('extractTextFromImage')
            ->once()
            ->with($imageUrl)
            ->andThrow(new RequestException(
                'Corrupted image data',
                new Request('POST', 'https://api.example.com/ocr'),
                new Response(422, [], '{"error": "Unable to process corrupted image"}')
            ))
        ;

        $result = $this->aiService->extractTextFromImage($imageUrl);

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('corrupted', strtolower($result['error']));
    }

    public function testAnalyzeTextWithApiKeyExpired(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new RequestException(
                'API key expired',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(401, [], '{"error": "API key has expired"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('api key', strtolower($result['error']));
    }

    public function testAnalyzeTextWithQuotaExceeded(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new RequestException(
                'Quota exceeded',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(402, [], '{"error": "Monthly quota exceeded"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('quota', strtolower($result['error']));
    }

    public function testAnalyzeTextWithConcurrentRequestLimit(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new RequestException(
                'Too many concurrent requests',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(429, [], '{"error": "Too many concurrent requests"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('concurrent', strtolower($result['error']));
    }

    public function testAnalyzeTextWithServiceMaintenance(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new RequestException(
                'Service temporarily unavailable',
                new Request('POST', 'https://api.example.com/analyze'),
                new Response(503, [], '{"error": "Service under maintenance"}')
            ))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('maintenance', strtolower($result['error']));
    }

    public function testAnalyzeTextWithUnexpectedException(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new \RuntimeException('Unexpected error occurred'))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('unexpected', strtolower($result['error']));
    }

    public function testAnalyzeTextWithMemoryExhaustion(): void
    {
        $this->mockTextAnalysisService
            ->shouldReceive('analyzeSentiment')
            ->once()
            ->with('Test text')
            ->andThrow(new \Error('Allowed memory size exhausted'))
        ;

        $result = $this->aiService->analyzeText('Test text');

        self::assertArrayHasKey('error', $result);
        $this->assertStringContains('memory', strtolower($result['error']));
    }
}
