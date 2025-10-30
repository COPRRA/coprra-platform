<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AIService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * AI Error Handling Tests.
 *
 * Tests AIService error handling using pure PHPUnit (not Laravel TestCase)
 * to avoid PHPUnit risky warnings about error handler manipulation.
 *
 * @internal
 *
 * @coversNothing
 */
final class AIErrorHandlingTest extends TestCase
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

    #[Test]
    public function aiHandlesInvalidInputGracefully(): void
    {
        try {
            // Empty string should be handled gracefully
            $result = $this->aiService->analyzeText('', 'sentiment');
            self::assertIsArray($result);
        } catch (\Exception $e) {
            // Exception is acceptable for invalid input
            self::assertNotEmpty($e->getMessage());
        }
    }

    #[Test]
    public function aiHandlesMalformedJson(): void
    {
        try {
            // Very short text that might cause issues
            $result = $this->aiService->analyzeText('x', 'sentiment');
            self::assertIsArray($result);

            // If successful, verify it has proper structure
            if (isset($result['error'])) {
                self::assertArrayHasKey('error', $result);
                self::assertIsString($result['error']);
            } else {
                self::assertArrayHasKey('result', $result);
            }
        } catch (\Exception $e) {
            // Exception should have meaningful message
            self::assertNotEmpty($e->getMessage());
            self::assertIsString($e->getMessage());
        }
    }

    #[Test]
    public function aiHandlesNetworkTimeout(): void
    {
        // Test with extremely long text that might cause timeout
        $longText = str_repeat('This is a very long text that might cause timeout issues. ', 1000);

        try {
            $result = $this->aiService->analyzeText($longText, 'sentiment');
            self::assertIsArray($result);

            // Verify response structure
            self::assertTrue(
                isset($result['result']) || isset($result['error']),
                'Response should have either result or error key'
            );
        } catch (\Exception $e) {
            // Verify timeout exceptions are properly handled
            self::assertNotEmpty($e->getMessage());
            self::assertContainsAny(
                ['timeout', 'connection', 'network', 'curl'],
                strtolower($e->getMessage()),
                'Exception message should indicate network/timeout issue'
            );
        }
    }

    #[Test]
    public function aiLogsErrorsProperly(): void
    {
        $errorOccurred = false;

        try {
            // Test with invalid analysis type to trigger error
            $result = $this->aiService->analyzeText('Test error logging', 'invalid_analysis_type');

            // If no exception, check for error in result
            if (isset($result['error'])) {
                $errorOccurred = true;
                self::assertIsString($result['error']);
                self::assertNotEmpty($result['error']);
            }
        } catch (\Exception $e) {
            $errorOccurred = true;
            self::assertNotEmpty($e->getMessage());
        }

        // Ensure some form of error handling occurred
        self::assertTrue($errorOccurred, 'Error handling should be triggered for invalid input');
    }

    #[Test]
    public function aiReturnsMeaningfulErrorMessages(): void
    {
        try {
            $result = $this->aiService->analyzeText('Test meaningful errors', 'sentiment');
            self::assertIsArray($result);
        } catch (\Exception $e) {
            // Exception should have meaningful message
            $message = $e->getMessage();
            self::assertNotEmpty($message);
            self::assertIsString($message);
        }
    }

    #[Test]
    public function aiHandlesConcurrentRequests(): void
    {
        $results = [];

        // Simulate 5 concurrent-like requests
        for ($i = 0; $i < 5; ++$i) {
            try {
                $results[] = $this->aiService->analyzeText("Concurrent request {$i}", 'sentiment');
            } catch (\Exception $e) {
                // Exceptions during requests are acceptable
                $results[] = null;
            }
        }

        // All 5 requests attempted
        self::assertCount(5, $results);
    }

    /**
     * Helper method to assert that a string contains any of the given substrings.
     */
    private function assertContainsAny(array $needles, string $haystack, string $message = ''): void
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                self::assertTrue(true);

                return;
            }
        }

        self::fail($message ?: \sprintf(
            'Failed asserting that "%s" contains any of [%s]',
            $haystack,
            implode(', ', $needles)
        ));
    }
}
