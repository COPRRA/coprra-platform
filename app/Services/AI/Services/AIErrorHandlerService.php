<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Enhanced error handling service for AI operations.
 */
class AIErrorHandlerService
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? app(LoggerInterface::class);
    }

    /**
     * Handle AI service errors with appropriate recovery strategies.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function handleError(\Exception $exception, string $operation, array $context = []): array
    {
        $errorType = $this->classifyError($exception);
        $errorData = [
            'operation' => $operation,
            'error_type' => $errorType,
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        $this->logError($errorData, $exception);

        return $this->generateFallbackResponse($operation, $errorType, $context);
    }

    /**
     * Check if error is recoverable (should retry).
     */
    public function isRecoverable(string $errorType): bool
    {
        return \in_array($errorType, [
            'network_error',
            'rate_limit_error',
            'service_unavailable',
        ], true);
    }

    /**
     * Get recommended retry delay based on error type.
     */
    public function getRetryDelay(string $errorType): int
    {
        switch ($errorType) {
            case 'network_error':
                return 1000; // 1 second

            case 'rate_limit_error':
                return 5000; // 5 seconds

            case 'service_unavailable':
                return 3000; // 3 seconds

            default:
                return 0; // No retry
        }
    }

    /**
     * Get error statistics for monitoring.
     *
     * @return array<string, mixed>
     */
    public function getErrorStats(): array
    {
        // This would typically integrate with a monitoring system
        // For now, return basic structure
        return [
            'total_errors' => 0,
            'error_types' => [],
            'last_24h' => 0,
            'recovery_rate' => 0.0,
        ];
    }

    /**
     * Classify error type for appropriate handling.
     */
    private function classifyError(\Exception $exception): string
    {
        $message = strtolower($exception->getMessage());

        // Network/Connection errors
        if (str_contains($message, 'connection')
            || str_contains($message, 'timeout')
            || str_contains($message, 'network')
            || str_contains($message, 'dns')
            || $exception instanceof ConnectionException) {
            return 'network_error';
        }

        // Authentication errors
        if (str_contains($message, 'unauthorized')
            || str_contains($message, 'authentication')
            || str_contains($message, 'api key')
            || (method_exists($exception, 'getResponse')
             && $exception->getResponse()
             && 401 === $exception->getResponse()->status())) {
            return 'authentication_error';
        }

        // Rate limiting errors
        if (str_contains($message, 'rate limit')
            || str_contains($message, 'too many requests')
            || (method_exists($exception, 'getResponse')
             && $exception->getResponse()
             && 429 === $exception->getResponse()->status())) {
            return 'rate_limit_error';
        }

        // Service unavailable errors
        if (str_contains($message, 'service unavailable')
            || str_contains($message, 'temporarily unavailable')
            || str_contains($message, 'circuit breaker')
            || (method_exists($exception, 'getResponse')
             && $exception->getResponse()
             && $exception->getResponse()->status() >= 500)) {
            return 'service_unavailable';
        }

        // Input validation errors
        if (str_contains($message, 'invalid input')
            || str_contains($message, 'validation')
            || str_contains($message, 'bad request')
            || (method_exists($exception, 'getResponse')
             && $exception->getResponse()
             && 400 === $exception->getResponse()->status())) {
            return 'validation_error';
        }

        // Quota/billing errors
        if (str_contains($message, 'quota')
            || str_contains($message, 'billing')
            || str_contains($message, 'insufficient credits')
            || (method_exists($exception, 'getResponse')
             && $exception->getResponse()
             && 402 === $exception->getResponse()->status())) {
            return 'quota_error';
        }

        return 'unknown_error';
    }

    /**
     * Log error with appropriate level based on error type.
     */
    private function logError(array $errorData, \Exception $exception): void
    {
        $errorType = $errorData['error_type'];

        switch ($errorType) {
            case 'network_error':
            case 'rate_limit_error':
            case 'service_unavailable':
                $this->logger->warning('⚠️ AI Service Warning', $errorData);

                break;

            case 'authentication_error':
            case 'quota_error':
                $this->logger->error('🚨 AI Service Critical Error', $errorData);

                break;

            case 'validation_error':
                $this->logger->info('ℹ️ AI Service Validation Error', $errorData);

                break;

            default:
                $this->logger->error('❌ AI Service Unknown Error', array_merge($errorData, [
                    'exception_class' => \get_class($exception),
                    'stack_trace' => $exception->getTraceAsString(),
                ]));

                break;
        }
    }

    /**
     * Generate appropriate fallback response based on operation and error type.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function generateFallbackResponse(string $operation, string $errorType, array $context): array
    {
        $baseResponse = [
            'success' => false,
            'error_type' => $errorType,
            'fallback_used' => true,
            'timestamp' => now()->toISOString(),
        ];

        switch ($operation) {
            case 'text_analysis':
                return array_merge($baseResponse, [
                    'sentiment' => 'neutral',
                    'confidence' => 0.0,
                    'categories' => ['عام'],
                    'keywords' => [],
                    'message' => $this->getFallbackMessage($errorType),
                ]);

            case 'product_classification':
                return array_merge($baseResponse, [
                    'category' => 'غير محدد',
                    'subcategory' => 'غير محدد',
                    'tags' => [],
                    'confidence' => 0.0,
                    'message' => $this->getFallbackMessage($errorType),
                ]);

            case 'recommendations':
                return array_merge($baseResponse, [
                    'recommendations' => [
                        'نعتذر، لا يمكن توليد توصيات في الوقت الحالي',
                        'يرجى المحاولة مرة أخرى لاحقاً',
                    ],
                    'confidence' => 0.0,
                    'recommendation_type' => 'fallback',
                    'message' => $this->getFallbackMessage($errorType),
                ]);

            case 'image_analysis':
                return array_merge($baseResponse, [
                    'category' => 'غير محدد',
                    'description' => 'لا يمكن تحليل الصورة في الوقت الحالي',
                    'recommendations' => [],
                    'sentiment' => 'neutral',
                    'confidence' => 0.0,
                    'message' => $this->getFallbackMessage($errorType),
                ]);

            default:
                return array_merge($baseResponse, [
                    'message' => $this->getFallbackMessage($errorType),
                    'data' => null,
                ]);
        }
    }

    /**
     * Get user-friendly fallback message based on error type.
     */
    private function getFallbackMessage(string $errorType): string
    {
        switch ($errorType) {
            case 'network_error':
                return 'مشكلة في الاتصال بخدمة الذكاء الاصطناعي. يرجى المحاولة مرة أخرى.';

            case 'authentication_error':
                return 'خطأ في المصادقة مع خدمة الذكاء الاصطناعي. يرجى التواصل مع الدعم الفني.';

            case 'rate_limit_error':
                return 'تم تجاوز الحد المسموح من الطلبات. يرجى المحاولة بعد قليل.';

            case 'service_unavailable':
                return 'خدمة الذكاء الاصطناعي غير متاحة مؤقتاً. يرجى المحاولة لاحقاً.';

            case 'validation_error':
                return 'البيانات المدخلة غير صحيحة. يرجى التحقق من المعلومات المدخلة.';

            case 'quota_error':
                return 'تم استنفاد حصة استخدام خدمة الذكاء الاصطناعي. يرجى التواصل مع الدعم الفني.';

            default:
                return 'حدث خطأ غير متوقع في خدمة الذكاء الاصطناعي. يرجى المحاولة مرة أخرى.';
        }
    }
}
