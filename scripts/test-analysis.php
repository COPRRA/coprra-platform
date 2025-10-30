<?php

declare(strict_types=1);

/**
 * COPRRA Test Coverage Analysis Script
 * Generates comprehensive test analysis without requiring coverage drivers.
 */

require_once __DIR__.'/../vendor/autoload.php';

class TestAnalyzer
{
    private array $testSuites = [];

    private array $sourceFiles = [];

    private array $testFiles = [];

    private array $analysis = [];

    public function __construct()
    {
        $this->loadTestSuites();
        $this->scanSourceFiles();
        $this->scanTestFiles();
    }

    public function analyze(): array
    {
        $this->analysis = [
            'summary' => $this->generateSummary(),
            'test_suites' => $this->analyzeTestSuites(),
            'coverage_gaps' => $this->identifyCoverageGaps(),
            'recommendations' => $this->generateRecommendations(),
        ];

        return $this->analysis;
    }

    public function generateReport(): string
    {
        $analysis = $this->analyze();

        $report = "# COPRRA Test Coverage Analysis Report\n";
        $report .= 'Generated: '.date('Y-m-d H:i:s')."\n\n";

        // Summary
        $report .= "## Summary\n";
        $report .= "- Total Source Files: {$analysis['summary']['total_source_files']}\n";
        $report .= "- Total Test Files: {$analysis['summary']['total_test_files']}\n";
        $report .= "- Test Suites: {$analysis['summary']['test_suites_count']}\n";
        $report .= "- Test-to-Source Ratio: {$analysis['summary']['test_to_source_ratio']}\n\n";

        // Test Suites
        $report .= "## Test Suite Analysis\n";
        foreach ($analysis['test_suites'] as $suite => $data) {
            $report .= "### {$suite}\n";
            $report .= "- Test Methods: {$data['test_methods']}\n";
            $report .= '- Directories: '.implode(', ', $data['directories'])."\n\n";
        }

        // Coverage Gaps
        $report .= "## Coverage Analysis\n";
        foreach ($analysis['coverage_gaps'] as $type => $data) {
            $report .= '### '.ucfirst($type)."\n";
            $report .= "- Total: {$data['total_'.rtrim($type, 's')]}\n";
            $report .= "- Tested: {$data['tested_'.rtrim($type, 's')]}\n";
            $report .= "- Coverage: {$data['coverage_percentage']}%\n\n";
        }

        // Recommendations
        $report .= "## Recommendations\n";
        foreach ($analysis['recommendations'] as $recommendation) {
            $report .= "- {$recommendation}\n";
        }

        return $report;
    }

    private function loadTestSuites(): void
    {
        $phpunitXml = simplexml_load_file(__DIR__.'/../phpunit.xml');

        foreach ($phpunitXml->testsuites->testsuite as $suite) {
            $this->testSuites[(string) $suite['name']] = [
                'name' => (string) $suite['name'],
                'directories' => [],
                'files' => [],
            ];

            foreach ($suite->directory ?? [] as $dir) {
                $this->testSuites[(string) $suite['name']]['directories'][] = (string) $dir;
            }
        }
    }

    private function scanSourceFiles(): void
    {
        $directories = ['app/COPRRA', 'app/Services', 'app/Http/Controllers', 'app/Models'];

        foreach ($directories as $dir) {
            $fullPath = __DIR__.'/../'.$dir;
            if (is_dir($fullPath)) {
                $this->scanDirectory($fullPath, $this->sourceFiles);
            }
        }
    }

    private function scanTestFiles(): void
    {
        $testDir = __DIR__.'/../tests';
        $this->scanDirectory($testDir, $this->testFiles);
    }

    private function scanDirectory(string $dir, array &$files): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ('php' === $file->getExtension()) {
                $files[] = $file->getPathname();
            }
        }
    }

    private function generateSummary(): array
    {
        return [
            'total_source_files' => count($this->sourceFiles),
            'total_test_files' => count($this->testFiles),
            'test_suites_count' => count($this->testSuites),
            'test_to_source_ratio' => round(count($this->testFiles) / max(count($this->sourceFiles), 1), 2),
        ];
    }

    private function analyzeTestSuites(): array
    {
        $suiteAnalysis = [];

        foreach ($this->testSuites as $suiteName => $suite) {
            $testCount = 0;
            $testMethods = [];

            foreach ($suite['directories'] as $dir) {
                $fullPath = __DIR__.'/../'.$dir;
                if (is_dir($fullPath)) {
                    $testCount += $this->countTestsInDirectory($fullPath, $testMethods);
                }
            }

            $suiteAnalysis[$suiteName] = [
                'test_count' => $testCount,
                'test_methods' => count($testMethods),
                'directories' => $suite['directories'],
            ];
        }

        return $suiteAnalysis;
    }

    private function countTestsInDirectory(string $dir, array &$testMethods): int
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ('php' === $file->getExtension() && str_contains($file->getFilename(), 'Test')) {
                $content = file_get_contents($file->getPathname());
                $matches = [];
                preg_match_all('/public function test\w+\(/', $content, $matches);
                $methodCount = count($matches[0]);
                $count += $methodCount;

                if ($methodCount > 0) {
                    $testMethods[] = [
                        'file' => $file->getPathname(),
                        'methods' => $methodCount,
                    ];
                }
            }
        }

        return $count;
    }

    private function identifyCoverageGaps(): array
    {
        $gaps = [];

        // Analyze Controllers
        $controllerFiles = array_filter($this->sourceFiles, static fn ($file) => str_contains($file, 'Controllers'));
        $controllerTests = array_filter($this->testFiles, static fn ($file) => str_contains($file, 'Controller'));

        $gaps['controllers'] = [
            'total_controllers' => count($controllerFiles),
            'tested_controllers' => count($controllerTests),
            'coverage_percentage' => count($controllerFiles) > 0 ? round((count($controllerTests) / count($controllerFiles)) * 100, 1) : 0,
        ];

        // Analyze Services
        $serviceFiles = array_filter($this->sourceFiles, static fn ($file) => str_contains($file, 'Services'));
        $serviceTests = array_filter($this->testFiles, static fn ($file) => str_contains($file, 'Service'));

        $gaps['services'] = [
            'total_services' => count($serviceFiles),
            'tested_services' => count($serviceTests),
            'coverage_percentage' => count($serviceFiles) > 0 ? round((count($serviceTests) / count($serviceFiles)) * 100, 1) : 0,
        ];

        // Analyze Models
        $modelFiles = array_filter($this->sourceFiles, static fn ($file) => str_contains($file, 'Models'));
        $modelTests = array_filter($this->testFiles, static fn ($file) => str_contains($file, 'Model'));

        $gaps['models'] = [
            'total_models' => count($modelFiles),
            'tested_models' => count($modelTests),
            'coverage_percentage' => count($modelFiles) > 0 ? round((count($modelTests) / count($modelFiles)) * 100, 1) : 0,
        ];

        return $gaps;
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Test coverage recommendations
        if ($this->analysis['summary']['test_to_source_ratio'] < 0.5) {
            $recommendations[] = 'Consider increasing test coverage - current ratio is '.$this->analysis['summary']['test_to_source_ratio'];
        }

        // Suite-specific recommendations
        foreach ($this->analysis['test_suites'] as $suite => $data) {
            if ($data['test_count'] < 10) {
                $recommendations[] = "Consider adding more tests to {$suite} suite (currently {$data['test_count']} tests)";
            }
        }

        return $recommendations;
    }
}

// Generate and save report
$analyzer = new TestAnalyzer();
$report = $analyzer->generateReport();

$reportsDir = __DIR__.'/../reports';
if (! is_dir($reportsDir)) {
    mkdir($reportsDir, 0755, true);
}

file_put_contents($reportsDir.'/test-coverage-analysis.md', $report);
echo "✅ Test coverage analysis report generated: reports/test-coverage-analysis.md\n";
echo $report;
