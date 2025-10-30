<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AI\ModelVersionTracker;
use App\Services\AI\PromptManager;
use App\Services\AI\Services\AIImageAnalysisService;
use App\Services\AI\Services\AIRequestService;
use App\Services\AI\Services\AITextAnalysisService;
use App\Services\AIService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

final class AIServiceProvider extends ServiceProvider
{
    /**
     * Register AI services.
     */
    public function register(): void
    {
        // Register PromptManager
        $this->app->singleton(PromptManager::class, static function (): PromptManager {
            return new PromptManager();
        });

        // Register ModelVersionTracker as singleton
        $this->app->singleton(ModelVersionTracker::class, static function (Application $app) {
            return new ModelVersionTracker();
        });

        // Register AIRequestService with configuration (read at runtime)
        $this->app->bind(AIRequestService::class, static function (Application $app): AIRequestService {
            return new AIRequestService(
                apiKey: config('ai.api_key', 'test_key'),
                baseUrl: config('ai.base_url', 'https://api.openai.com/v1'),
                timeout: config('ai.timeout', 60)
            );
        });

        // Register AITextAnalysisService
        $this->app->singleton(AITextAnalysisService::class, static function (Application $app): AITextAnalysisService {
            return new AITextAnalysisService(
                $app->make(AIRequestService::class),
                $app->make(PromptManager::class)
            );
        });

        // Register AIImageAnalysisService
        $this->app->singleton(AIImageAnalysisService::class, static function (Application $app): AIImageAnalysisService {
            return new AIImageAnalysisService(
                $app->make(AIRequestService::class),
                $app->make(PromptManager::class)
            );
        });

        // Register main AIService
        $this->app->singleton(AIService::class, static function (Application $app): AIService {
            return new AIService(
                $app->make(AITextAnalysisService::class),
                $app->make(AIImageAnalysisService::class)
            );
        });
    }
}
