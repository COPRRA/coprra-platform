<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;

class AIServiceTest extends TestCase
{
    public function test_service_exists(): void
    {
        $this->assertTrue(class_exists('App\\Services\\AIService'));
    }
}
