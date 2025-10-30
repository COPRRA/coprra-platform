<?php

declare(strict_types=1);

/**
 * Convert test files from @test annotations to #[Test] attributes.
 */
$files = [
    'tests/Feature/Services/PaymentServiceTest.php',
    'tests/Feature/Services/OrderServiceTest.php',
    'tests/Feature/Services/AIServiceTest.php',
];

foreach ($files as $file) {
    if (! file_exists($file)) {
        echo "❌ File not found: {$file}\n";

        continue;
    }

    $content = file_get_contents($file);
    $originalContent = $content;

    // Add Test attribute import if not present
    if (! str_contains($content, 'use PHPUnit\Framework\Attributes\Test;')) {
        // Find the last use statement and add after it
        $content = preg_replace(
            '/(use Tests\TestCase;)/',
            "use PHPUnit\\Framework\\Attributes\\Test;\n$1",
            $content,
            1
        );
    }

    // Replace @test annotation with #[Test] attribute
    // Pattern: /**\n     * @test\n     * (@covers ...)?\n     */\n    public function
    // Replace with: #[Test]\n    public function

    $content = preg_replace(
        '/\/\*\*\s*\n\s*\*\s*@test\s*\n(\s*\*\s*@covers[^\n]*\n)*\s*\*\/\s*\n(\s*)public function/m',
        "#[Test]\n$2public function",
        $content
    );

    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "✅ Converted: {$file}\n";
    } else {
        echo "⚠️  No changes needed: {$file}\n";
    }
}

echo "\n✅ Conversion complete!\n";
