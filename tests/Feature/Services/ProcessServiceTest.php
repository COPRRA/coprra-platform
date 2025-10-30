<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\ProcessResult;
use App\Services\ProcessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProcessServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProcessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProcessService();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testRunsProcessCommandSuccessfully()
    {
        // Arrange
        $command = 'ls -la';
        $exitCode = 0;
        $output = 'total 0';
        $errorOutput = '';

        $processResult = \Mockery::mock();
        $processResult->shouldReceive('exitCode')->andReturn($exitCode);
        $processResult->shouldReceive('output')->andReturn($output);
        $processResult->shouldReceive('errorOutput')->andReturn($errorOutput);

        // Support chaining: Process::timeout(...)->run($command)
        Process::shouldReceive('timeout')
            ->with(\Mockery::type('int'))
            ->andReturnSelf()
        ;

        Process::shouldReceive('run')
            ->with($command)
            ->andReturn($processResult)
        ;

        // Act
        $result = $this->service->run($command);

        // Assert
        self::assertInstanceOf(ProcessResult::class, $result);
        self::assertSame($exitCode, $result->exitCode);
        self::assertSame($output, $result->output);
        self::assertSame($errorOutput, $result->errorOutput);
    }

    public function testProcessesDataSuccessfully()
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->process($data);

        // Assert
        self::assertIsArray($result);
        self::assertTrue($result['processed']);
        self::assertArrayHasKey('data', $result);
    }

    public function testHandlesNullData()
    {
        // Act
        $result = $this->service->process(null);

        // Assert
        self::assertIsArray($result);
        self::assertTrue($result['error']);
        self::assertSame('Invalid data provided', $result['message']);
    }

    public function testValidatesDataSuccessfully()
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->validate($data);

        // Assert
        self::assertTrue($result);
    }

    public function testValidatesDataWithEmptyName()
    {
        // Arrange
        $data = [
            'name' => '',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->validate($data);

        // Assert
        self::assertFalse($result);
        $errors = $this->service->getErrors();
        self::assertArrayHasKey('name', $errors);
    }

    public function testCleansDataSuccessfully()
    {
        // Arrange
        $data = [
            'name' => '  John Doe  ',
            'email' => '  JOHN@EXAMPLE.COM  ',
        ];

        // Act
        $result = $this->service->clean($data);

        // Assert
        self::assertSame('John Doe', $result['name']);
        self::assertSame('john@example.com', $result['email']);
    }

    public function testTransformsDataSuccessfully()
    {
        // Arrange
        $data = [
            'name' => 'john doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->transform($data);

        // Assert
        self::assertSame('John doe', $result['name']);
        self::assertSame('John@example.com', $result['email']);
    }

    public function testGetsProcessingStatus()
    {
        // Act
        $status = $this->service->getStatus();

        // Assert
        self::assertSame('idle', $status);
    }

    public function testResetsService()
    {
        // Arrange
        $data = ['name' => ''];
        $this->service->process($data);

        // Act
        $this->service->reset();

        // Assert
        self::assertSame('idle', $this->service->getStatus());
        self::assertEmpty($this->service->getErrors());
    }

    public function testGetsProcessingMetrics()
    {
        // Arrange
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $this->service->process($data);

        // Act
        $metrics = $this->service->getMetrics();

        // Assert
        self::assertIsArray($metrics);
        self::assertArrayHasKey('processed_count', $metrics);
        self::assertArrayHasKey('error_count', $metrics);
    }
}
