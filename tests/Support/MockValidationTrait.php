<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Trait for validating mock method signatures against real classes.
 */
trait MockValidationTrait
{
    /**
     * Validate that a method exists on a class with the expected signature.
     */
    protected function assertMethodExists(string $className, string $methodName, string $message = ''): void
    {
        $this->assertTrue(
            method_exists($className, $methodName),
            $message ?: "Method {$methodName} must exist on {$className}"
        );
    }

    /**
     * Validate method signature matches expected parameters.
     */
    protected function assertMethodSignature(
        string $className,
        string $methodName,
        array $expectedParameterTypes = [],
        ?string $expectedReturnType = null
    ): void {
        $this->assertMethodExists($className, $methodName);

        $reflection = new \ReflectionMethod($className, $methodName);
        $parameters = $reflection->getParameters();

        // Check parameter count
        $this->assertCount(
            \count($expectedParameterTypes),
            $parameters,
            "Method {$className}::{$methodName} should have ".\count($expectedParameterTypes).' parameters'
        );

        // Check parameter types
        foreach ($expectedParameterTypes as $index => $expectedType) {
            $parameter = $parameters[$index];
            $actualType = $parameter->getType();

            if ($actualType) {
                $this->assertEquals(
                    $expectedType,
                    $actualType->getName(),
                    "Parameter {$index} of {$className}::{$methodName} should be of type {$expectedType}"
                );
            }
        }

        // Check return type
        if (null !== $expectedReturnType) {
            $returnType = $reflection->getReturnType();
            $this->assertNotNull($returnType, "Method {$className}::{$methodName} should have a return type");
            $this->assertEquals(
                $expectedReturnType,
                $returnType->getName(),
                "Method {$className}::{$methodName} should return {$expectedReturnType}"
            );
        }
    }

    /**
     * Validate that a class implements an interface.
     */
    protected function assertImplementsInterface(string $className, string $interfaceName): void
    {
        $reflection = new \ReflectionClass($className);
        $this->assertTrue(
            $reflection->implementsInterface($interfaceName),
            "Class {$className} must implement {$interfaceName}"
        );
    }

    /**
     * Validate mock setup for common service dependencies.
     */
    protected function validateServiceMock(string $serviceClass, array $requiredMethods): void
    {
        foreach ($requiredMethods as $method) {
            $this->assertMethodExists($serviceClass, $method);
        }
    }

    /**
     * Validate that mocked methods actually exist on the target class.
     */
    protected function validateMockMethods(string $className, array $mockedMethods): void
    {
        foreach ($mockedMethods as $method) {
            $this->assertMethodExists(
                $className,
                $method,
                "Mocked method '{$method}' does not exist on {$className}. This mock will fail at runtime."
            );
        }
    }

    /**
     * Check if a class has all required dependencies for proper mocking.
     */
    protected function validateClassDependencies(string $className, array $expectedDependencies): void
    {
        $reflection = new \ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            $this->assertEmpty(
                $expectedDependencies,
                "Class {$className} has no constructor but dependencies were expected"
            );

            return;
        }

        $parameters = $constructor->getParameters();
        $this->assertCount(
            \count($expectedDependencies),
            $parameters,
            "Class {$className} constructor should have ".\count($expectedDependencies).' dependencies'
        );

        foreach ($expectedDependencies as $index => $expectedType) {
            $parameter = $parameters[$index];
            $actualType = $parameter->getType();

            if ($actualType) {
                $this->assertEquals(
                    $expectedType,
                    $actualType->getName(),
                    "Constructor parameter {$index} of {$className} should be of type {$expectedType}"
                );
            }
        }
    }
}
