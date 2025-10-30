<?php

declare(strict_types=1);

namespace Tests\AI;

use App\Services\AI\StrictQualityAgent;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class StrictQualityAgentTest extends TestCase
{
    private StrictQualityAgent $agent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agent = new StrictQualityAgent();
    }

    public function testAgentInitializesCorrectly(): void
    {
        self::assertInstanceOf(StrictQualityAgent::class, $this->agent);
    }

    public function testAgentHasAllRequiredStages(): void
    {
        // Use reflection to access private stages property
        $reflection = new \ReflectionClass($this->agent);
        $stagesProperty = $reflection->getProperty('stages');
        $stagesProperty->setAccessible(true);
        $stages = $stagesProperty->getValue($this->agent);

        $expectedStages = [
            'syntax_check',
            'phpstan_analysis',
            'phpmd_quality',
            'pint_formatting',
            'composer_audit',
            'unit_tests',
            'feature_tests',
            'ai_tests',
            'security_tests',
            'performance_tests',
            'integration_tests',
            'e2e_tests',
            'link_checker',
        ];

        foreach ($expectedStages as $expectedStage) {
            self::assertArrayHasKey($expectedStage, $stages);
        }

        self::assertCount(\count($expectedStages), $stages);
    }

    public function testAgentStagesHaveRequiredProperties(): void
    {
        $reflection = new \ReflectionClass($this->agent);
        $stagesProperty = $reflection->getProperty('stages');
        $stagesProperty->setAccessible(true);
        $stages = $stagesProperty->getValue($this->agent);

        foreach ($stages as $stage) {
            self::assertObjectHasProperty('name', $stage);
            self::assertObjectHasProperty('command', $stage);
            self::assertObjectHasProperty('strict', $stage);
            self::assertObjectHasProperty('required', $stage);
            self::assertTrue($stage->strict);
            self::assertTrue($stage->required);
        }
    }

    public function testExecuteAllStagesReturnsCorrectStructure(): void
    {
        // Mock Process facade to avoid actual command execution
        Process::fake([
            '*' => Process::result(output: 'Success', exitCode: 0),
        ]);

        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('put')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $result = $this->agent->executeAllStages();

        self::assertIsArray($result);
        self::assertArrayHasKey('overall_success', $result);
        self::assertArrayHasKey('stages', $result);
        self::assertArrayHasKey('errors', $result);
        self::assertArrayHasKey('fixes', $result);
        self::assertIsBool($result['overall_success']);
        self::assertIsArray($result['stages']);
        self::assertIsArray($result['errors']);
        self::assertIsArray($result['fixes']);
    }

    public function testAgentHandlesStageFailure(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Error', exitCode: 1, errorOutput: 'Command failed'),
        ]);

        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('put')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $result = $this->agent->executeAllStages();

        self::assertFalse($result['overall_success']);
        self::assertNotEmpty($result['stages']);

        // Check that at least one stage failed
        $hasFailedStage = false;
        foreach ($result['stages'] as $stageResult) {
            if (! $stageResult->success) {
                $hasFailedStage = true;

                break;
            }
        }
        self::assertTrue($hasFailedStage);
    }

    public function testAgentGeneratesReportFile(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Success', exitCode: 0),
        ]);

        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('put')
            ->once()
            ->with(storage_path('logs/ai-quality-report.json'), \Mockery::type('string'))
        ;
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $this->agent->executeAllStages();
    }

    public function testStageResultsHaveCorrectStructure(): void
    {
        Process::fake([
            '*' => Process::result(output: 'Success', exitCode: 0),
        ]);

        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('put')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $result = $this->agent->executeAllStages();

        foreach ($result['stages'] as $stageResult) {
            self::assertObjectHasProperty('success', $stageResult);
            self::assertObjectHasProperty('output', $stageResult);
            self::assertObjectHasProperty('errors', $stageResult);
            self::assertObjectHasProperty('duration', $stageResult);
            self::assertObjectHasProperty('timestamp', $stageResult);
            self::assertIsBool($stageResult->success);
            self::assertIsString($stageResult->output);
            self::assertIsArray($stageResult->errors);
            self::assertIsFloat($stageResult->duration);
            self::assertIsString($stageResult->timestamp);
        }
    }

    public function testAgentHandlesExceptionsDuringStageExecution(): void
    {
        Process::shouldReceive('run')
            ->andThrow(new \Exception('Test exception'))
        ;

        File::shouldReceive('exists')->andReturn(true);
        File::shouldReceive('allFiles')->andReturn([]);
        File::shouldReceive('put')->once();
        Log::shouldReceive('info')->zeroOrMoreTimes();

        $result = $this->agent->executeAllStages();

        self::assertFalse($result['overall_success']);
        self::assertNotEmpty($result['errors']);
    }
}
