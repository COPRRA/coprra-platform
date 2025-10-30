<?php

declare(strict_types=1);

/**
 * Coverage Analysis Script
 * Parses PHPUnit coverage XML files and generates comprehensive coverage report.
 */
$coverageDir = __DIR__.'/storage/logs/coverage/feature-coverage.xml';

if (! is_dir($coverageDir)) {
    echo "Coverage directory not found: {$coverageDir}\n";

    exit(1);
}

// Recursively find all XML files
$xmlFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($coverageDir),
    RecursiveIteratorIterator::SELF_FIRST
);

$coverageData = [];
$totalStats = [
    'files' => 0,
    'lines_total' => 0,
    'lines_executed' => 0,
    'methods_total' => 0,
    'methods_tested' => 0,
];

foreach ($xmlFiles as $file) {
    if (! $file->isFile() || 'xml' !== $file->getExtension()) {
        continue;
    }

    $xml = @simplexml_load_file($file->getPathname());
    if (false === $xml) {
        continue;
    }

    $fileName = (string) $xml->file['name'];
    $filePath = (string) $xml->file['path'];
    $fullPath = trim($filePath, '/').'/'.$fileName;

    // Get totals
    $totals = $xml->file->totals;
    $lines = $totals->lines;
    $methods = $totals->methods;

    $linePercent = (float) $lines['percent'];
    $methodPercent = (float) $methods['percent'];

    // Get class info
    $className = '';
    $crapScore = 0;
    $methodDetails = [];

    if (isset($xml->file->class)) {
        $class = $xml->file->class;
        $className = (string) $class['name'];
        $crapScore = (int) $class['crap'];

        // Get method details
        foreach ($class->method as $method) {
            $methodName = (string) $method['name'];
            $methodCrap = (int) $method['crap'];
            $methodExecutable = (int) $method['executable'];
            $methodExecuted = (int) $method['executed'];
            $methodCoverage = (float) $method['coverage'];

            $methodDetails[] = [
                'name' => $methodName,
                'crap' => $methodCrap,
                'executable' => $methodExecutable,
                'executed' => $methodExecuted,
                'coverage' => $methodCoverage,
            ];
        }
    }

    $coverageData[] = [
        'file' => $fullPath,
        'class' => $className,
        'lines_total' => (int) $lines['total'],
        'lines_executable' => (int) $lines['executable'],
        'lines_executed' => (int) $lines['executed'],
        'line_percent' => $linePercent,
        'methods_total' => (int) $methods['count'],
        'methods_tested' => (int) $methods['tested'],
        'method_percent' => $methodPercent,
        'crap_score' => $crapScore,
        'methods' => $methodDetails,
    ];

    // Update totals
    ++$totalStats['files'];
    $totalStats['lines_total'] += (int) $lines['total'];
    $totalStats['lines_executed'] += (int) $lines['executed'];
    $totalStats['methods_total'] += (int) $methods['count'];
    $totalStats['methods_tested'] += (int) $methods['tested'];
}

// Calculate overall percentages
$overallLinePercent = $totalStats['lines_total'] > 0
    ? ($totalStats['lines_executed'] / $totalStats['lines_total']) * 100
    : 0;

$overallMethodPercent = $totalStats['methods_total'] > 0
    ? ($totalStats['methods_tested'] / $totalStats['methods_total']) * 100
    : 0;

// Categorize files
$serviceFiles = [];
$controllerFiles = [];
$validatorFiles = [];
$helperFiles = [];
$modelFiles = [];
$otherFiles = [];

foreach ($coverageData as $data) {
    if (str_contains($data['file'], '/Services/')) {
        $serviceFiles[] = $data;
    } elseif (str_contains($data['file'], '/Controllers/')) {
        $controllerFiles[] = $data;
    } elseif (str_contains($data['file'], '/Rules/') || str_contains($data['file'], '/Requests/')) {
        $validatorFiles[] = $data;
    } elseif (str_contains($data['file'], '/Helpers/')) {
        $helperFiles[] = $data;
    } elseif (str_contains($data['file'], '/Models/')) {
        $modelFiles[] = $data;
    } else {
        $otherFiles[] = $data;
    }
}

// Sort by coverage (lowest first)
usort($coverageData, static fn ($a, $b) => $a['line_percent'] <=> $b['line_percent']);

// Output Report
echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "                    COMPREHENSIVE COVERAGE ANALYSIS REPORT                  \n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "\n";

// Overall Statistics
echo "📊 OVERALL STATISTICS\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";
echo 'Total Files Analyzed:      '.$totalStats['files']."\n";
echo 'Total Lines:               '.number_format($totalStats['lines_total'])."\n";
echo 'Lines Executed:            '.number_format($totalStats['lines_executed'])."\n";
echo 'Overall Line Coverage:     '.number_format($overallLinePercent, 2)."%\n";
echo "\n";
echo 'Total Methods:             '.number_format($totalStats['methods_total'])."\n";
echo 'Methods Tested:            '.number_format($totalStats['methods_tested'])."\n";
echo 'Overall Method Coverage:   '.number_format($overallMethodPercent, 2)."%\n";
echo "\n";

// Category Statistics
echo "📁 COVERAGE BY CATEGORY\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$categories = [
    'Services' => $serviceFiles,
    'Controllers' => $controllerFiles,
    'Validators/Requests' => $validatorFiles,
    'Helpers' => $helperFiles,
    'Models' => $modelFiles,
    'Others' => $otherFiles,
];

foreach ($categories as $category => $files) {
    if (empty($files)) {
        continue;
    }

    $catLinesTotal = array_sum(array_column($files, 'lines_total'));
    $catLinesExecuted = array_sum(array_column($files, 'lines_executed'));
    $catPercent = $catLinesTotal > 0 ? ($catLinesExecuted / $catLinesTotal) * 100 : 0;

    $icon = $catPercent >= 90 ? '✅' : ($catPercent >= 70 ? '⚠️' : '❌');

    echo sprintf(
        "%s %-25s %5d files  %6.2f%% coverage\n",
        $icon,
        $category.':',
        count($files),
        $catPercent
    );
}
echo "\n";

// Critical Files with <70% Coverage
echo "🔴 CRITICAL GAPS - FILES WITH <70% COVERAGE\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$lowCoverageFiles = array_filter($coverageData, static fn ($d) => $d['line_percent'] < 70);
$criticalLowCoverage = [];

// Focus on Services, Validators, Helpers (business logic)
foreach ($lowCoverageFiles as $data) {
    if (str_contains($data['file'], '/Services/')
        || str_contains($data['file'], '/Rules/')
        || str_contains($data['file'], '/Helpers/')
        || str_contains($data['file'], '/Validators/')) {
        $criticalLowCoverage[] = $data;
    }
}

if (empty($criticalLowCoverage)) {
    echo "✅ No critical files with <70% coverage!\n";
} else {
    echo sprintf(
        "Found %d critical files with low coverage:\n\n",
        count($criticalLowCoverage)
    );

    foreach (array_slice($criticalLowCoverage, 0, 20) as $data) {
        $icon = 0 === $data['line_percent'] ? '❌' : '⚠️';
        echo sprintf(
            "%s %6.2f%%  %s\n",
            $icon,
            $data['line_percent'],
            $data['file']
        );

        // Show untested methods
        $untestedMethods = array_filter($data['methods'], static fn ($m) => 0 === $m['coverage']);
        if (! empty($untestedMethods) && count($untestedMethods) <= 5) {
            foreach ($untestedMethods as $method) {
                echo sprintf("           └─ %s() [CRAP: %d]\n", $method['name'], $method['crap']);
            }
        }
    }
}
echo "\n";

// High Complexity, Low Coverage (CRAP > 30, coverage < 50%)
echo "⚠️  HIGH COMPLEXITY + LOW COVERAGE (CRAP > 30)\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$highComplexityLowCoverage = array_filter(
    $coverageData,
    static fn ($d) => $d['crap_score'] > 30 && $d['line_percent'] < 50
);

if (empty($highComplexityLowCoverage)) {
    echo "✅ No high complexity files with low coverage!\n";
} else {
    usort($highComplexityLowCoverage, static fn ($a, $b) => $b['crap_score'] <=> $a['crap_score']);

    foreach (array_slice($highComplexityLowCoverage, 0, 15) as $data) {
        echo sprintf(
            "❌ CRAP %4d  Coverage %5.1f%%  %s\n",
            $data['crap_score'],
            $data['line_percent'],
            $data['file']
        );
    }
}
echo "\n";

// Services Coverage Detail
echo "🔧 SERVICES LAYER COVERAGE ANALYSIS (Target: 90%+)\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

if (empty($serviceFiles)) {
    echo "No service files found in coverage data.\n";
} else {
    $servicesLowCoverage = array_filter($serviceFiles, static fn ($d) => $d['line_percent'] < 90);

    if (empty($servicesLowCoverage)) {
        echo "✅ All services have ≥90% coverage!\n";
    } else {
        echo sprintf("Found %d services with <90%% coverage:\n\n", count($servicesLowCoverage));

        usort($servicesLowCoverage, static fn ($a, $b) => $a['line_percent'] <=> $b['line_percent']);

        foreach (array_slice($servicesLowCoverage, 0, 25) as $data) {
            $icon = $data['line_percent'] < 70 ? '❌' : '⚠️';
            $serviceName = basename($data['file'], '.php');

            echo sprintf(
                "%s %6.2f%%  %-40s  Methods: %d/%d tested\n",
                $icon,
                $data['line_percent'],
                $serviceName,
                $data['methods_tested'],
                $data['methods_total']
            );
        }
    }
}
echo "\n";

// Files with 0% Coverage
echo "❌ ZERO COVERAGE FILES (Priority for testing)\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$zeroCoverageFiles = array_filter($coverageData, static fn ($d) => 0 === $d['line_percent']);

// Prioritize Services, Helpers, Validators
$zeroCoveragePriority = [];
foreach ($zeroCoverageFiles as $data) {
    $priority = 0;
    if (str_contains($data['file'], '/Services/')) {
        $priority = 3;
    } elseif (str_contains($data['file'], '/Helpers/')) {
        $priority = 3;
    } elseif (str_contains($data['file'], '/Rules/')) {
        $priority = 3;
    } elseif (str_contains($data['file'], '/Validators/')) {
        $priority = 3;
    } elseif (str_contains($data['file'], '/Controllers/')) {
        $priority = 2;
    } else {
        $priority = 1;
    }

    $zeroCoveragePriority[] = array_merge($data, ['priority' => $priority]);
}

usort($zeroCoveragePriority, static fn ($a, $b) => $b['priority'] <=> $a['priority']);

$count = 0;
foreach ($zeroCoveragePriority as $data) {
    if ($count >= 30) {
        break;
    }

    $priorityLabel = ['Low', 'Med', 'High', 'CRITICAL'][$data['priority']];

    echo sprintf(
        "%-10s  %s\n",
        "[{$priorityLabel}]",
        $data['file']
    );

    ++$count;
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "                              END OF REPORT                                  \n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "\n";

// Export JSON for further analysis
$jsonReport = [
    'timestamp' => date('Y-m-d H:i:s'),
    'overall' => [
        'files' => $totalStats['files'],
        'line_coverage' => $overallLinePercent,
        'method_coverage' => $overallMethodPercent,
    ],
    'categories' => [],
    'critical_gaps' => $criticalLowCoverage,
    'zero_coverage' => $zeroCoveragePriority,
    'high_complexity_low_coverage' => $highComplexityLowCoverage,
];

foreach ($categories as $category => $files) {
    if (empty($files)) {
        continue;
    }

    $catLinesTotal = array_sum(array_column($files, 'lines_total'));
    $catLinesExecuted = array_sum(array_column($files, 'lines_executed'));
    $catPercent = $catLinesTotal > 0 ? ($catLinesExecuted / $catLinesTotal) * 100 : 0;

    $jsonReport['categories'][$category] = [
        'files' => count($files),
        'coverage' => $catPercent,
    ];
}

file_put_contents(__DIR__.'/reports/coverage-analysis.json', json_encode($jsonReport, JSON_PRETTY_PRINT));

echo "✅ JSON report saved to: reports/coverage-analysis.json\n";
