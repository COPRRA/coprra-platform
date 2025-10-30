<?php

declare(strict_types=1);

/**
 * Performance Baseline Comparison Script.
 *
 * This script compares current performance measurements with previous
 * baselines to detect performance regressions.
 */

require_once __DIR__.'/../vendor/autoload.php';

class compare_performance_baselines
{
    private array $currentBaseline;
    private array $previousBaseline;
    private array $thresholds;
    private array $regressions = [];

    public function __construct()
    {
        $this->loadBaselines();
        $this->loadThresholds();
    }

    public function compareBaselines(): array
    {
        echo "Comparing performance baselines...\n";

        $currentMeasurements = $this->currentBaseline['measurements'] ?? [];
        $previousMeasurements = $this->previousBaseline['measurements'] ?? [];

        $comparison = [
            'timestamp' => date('Y-m-d H:i:s'),
            'current_baseline_date' => $this->currentBaseline['last_updated'] ?? 'unknown',
            'previous_baseline_date' => $this->previousBaseline['last_updated'] ?? 'unknown',
            'total_endpoints' => count($currentMeasurements),
            'regression_count' => 0,
            'regressions' => [],
            'improvements' => [],
            'regression_detected' => false,
            'summary' => [],
        ];

        foreach ($currentMeasurements as $endpoint => $currentData) {
            if (! isset($previousMeasurements[$endpoint])) {
                echo "  New endpoint: {$endpoint}\n";

                continue;
            }

            $previousData = $previousMeasurements[$endpoint];
            $endpointComparison = $this->compareEndpoint($endpoint, $currentData, $previousData);

            if ($endpointComparison['has_regression']) {
                $comparison['regressions'][$endpoint] = $endpointComparison['regressions'];
                ++$comparison['regression_count'];
                $comparison['regression_detected'] = true;

                echo "  ⚠️  Regression in {$endpoint}:\n";
                foreach ($endpointComparison['regressions'] as $regression) {
                    echo "    - {$regression['metric']}: {$regression['percentage']}% increase\n";
                }
            } elseif ($endpointComparison['has_improvement']) {
                $comparison['improvements'][$endpoint] = $endpointComparison['improvements'];

                echo "  ✅ Improvement in {$endpoint}:\n";
                foreach ($endpointComparison['improvements'] as $improvement) {
                    echo "    - {$improvement['metric']}: {$improvement['percentage']}% decrease\n";
                }
            } else {
                echo "  ➡️  No significant change in {$endpoint}\n";
            }
        }

        // Calculate summary statistics
        $comparison['summary'] = $this->calculateSummary($currentMeasurements, $previousMeasurements);

        // Save comparison results
        $this->saveComparison($comparison);

        return $comparison;
    }

    public function printSummary(array $comparison): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "PERFORMANCE COMPARISON SUMMARY\n";
        echo str_repeat('=', 60)."\n";

        if ($comparison['regression_detected']) {
            echo "🚨 REGRESSION DETECTED!\n";
            echo "Regressions found: {$comparison['regression_count']}\n";
        } else {
            echo "✅ No performance regressions detected\n";
        }

        echo "\nOverall changes:\n";
        echo "- Response time: {$comparison['summary']['response_time_change']}%\n";
        echo "- Memory usage: {$comparison['summary']['memory_usage_change']}%\n";
        echo "- Query count: {$comparison['summary']['query_count_change']}%\n";

        echo "\nCurrent averages:\n";
        echo "- Response time: {$comparison['summary']['avg_response_time']}ms\n";
        echo "- Memory usage: {$comparison['summary']['avg_memory_usage']}MB\n";
        echo "- Query count: {$comparison['summary']['avg_query_count']}\n";

        echo str_repeat('=', 60)."\n";
    }

    private function loadBaselines(): void
    {
        $currentPath = storage_path('performance_baselines.json');
        $previousPath = storage_path('performance_baselines_previous.json');

        if (! file_exists($currentPath)) {
            throw new Exception('Current baseline file not found');
        }

        if (! file_exists($previousPath)) {
            throw new Exception('Previous baseline file not found');
        }

        $this->currentBaseline = json_decode(file_get_contents($currentPath), true);
        $this->previousBaseline = json_decode(file_get_contents($previousPath), true);
    }

    private function loadThresholds(): void
    {
        // Load thresholds from config or use defaults
        $this->thresholds = [
            'response_time_increase' => 0.20,  // 20% increase
            'memory_usage_increase' => 0.25,   // 25% increase
            'query_count_increase' => 0.30,    // 30% increase
        ];

        // Try to load from Laravel config if available
        try {
            $app = require __DIR__.'/../bootstrap/app.php';
            $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

            $configThresholds = config('performance_benchmarks.regression_detection.regression_thresholds');
            if ($configThresholds) {
                $this->thresholds = array_merge($this->thresholds, $configThresholds);
            }
        } catch (Exception $e) {
            // Use default thresholds if config loading fails
        }
    }

    private function compareEndpoint(string $endpoint, array $current, array $previous): array
    {
        $result = [
            'has_regression' => false,
            'has_improvement' => false,
            'regressions' => [],
            'improvements' => [],
        ];

        $metrics = [
            'avg_response_time' => 'response_time_increase',
            'avg_memory_usage' => 'memory_usage_increase',
            'avg_query_count' => 'query_count_increase',
        ];

        foreach ($metrics as $metric => $thresholdKey) {
            $currentValue = $current[$metric] ?? 0;
            $previousValue = $previous[$metric] ?? 0;

            if (0 === $previousValue) {
                continue; // Skip if previous value is 0 to avoid division by zero
            }

            $percentageChange = (($currentValue - $previousValue) / $previousValue) * 100;
            $threshold = $this->thresholds[$thresholdKey] * 100; // Convert to percentage

            if ($percentageChange > $threshold) {
                $result['has_regression'] = true;
                $result['regressions'][] = [
                    'metric' => $metric,
                    'current_value' => $currentValue,
                    'previous_value' => $previousValue,
                    'percentage' => round($percentageChange, 2),
                    'threshold' => $threshold,
                ];
            } elseif ($percentageChange < -10) { // 10% improvement threshold
                $result['has_improvement'] = true;
                $result['improvements'][] = [
                    'metric' => $metric,
                    'current_value' => $currentValue,
                    'previous_value' => $previousValue,
                    'percentage' => round(abs($percentageChange), 2),
                ];
            }
        }

        return $result;
    }

    private function calculateSummary(array $current, array $previous): array
    {
        $summary = [
            'avg_response_time' => 0,
            'avg_memory_usage' => 0,
            'avg_query_count' => 0,
            'response_time_change' => 0,
            'memory_usage_change' => 0,
            'query_count_change' => 0,
        ];

        $totalEndpoints = count($current);
        if (0 === $totalEndpoints) {
            return $summary;
        }

        // Calculate current averages
        $totalResponseTime = 0;
        $totalMemoryUsage = 0;
        $totalQueryCount = 0;

        // Calculate previous averages
        $totalPreviousResponseTime = 0;
        $totalPreviousMemoryUsage = 0;
        $totalPreviousQueryCount = 0;

        foreach ($current as $endpoint => $data) {
            $totalResponseTime += $data['avg_response_time'] ?? 0;
            $totalMemoryUsage += $data['avg_memory_usage'] ?? 0;
            $totalQueryCount += $data['avg_query_count'] ?? 0;

            if (isset($previous[$endpoint])) {
                $totalPreviousResponseTime += $previous[$endpoint]['avg_response_time'] ?? 0;
                $totalPreviousMemoryUsage += $previous[$endpoint]['avg_memory_usage'] ?? 0;
                $totalPreviousQueryCount += $previous[$endpoint]['avg_query_count'] ?? 0;
            }
        }

        $summary['avg_response_time'] = round($totalResponseTime / $totalEndpoints, 2);
        $summary['avg_memory_usage'] = round($totalMemoryUsage / $totalEndpoints, 2);
        $summary['avg_query_count'] = round($totalQueryCount / $totalEndpoints, 2);

        // Calculate percentage changes
        if ($totalPreviousResponseTime > 0) {
            $summary['response_time_change'] = round(
                (($totalResponseTime - $totalPreviousResponseTime) / $totalPreviousResponseTime) * 100,
                2
            );
        }

        if ($totalPreviousMemoryUsage > 0) {
            $summary['memory_usage_change'] = round(
                (($totalMemoryUsage - $totalPreviousMemoryUsage) / $totalPreviousMemoryUsage) * 100,
                2
            );
        }

        if ($totalPreviousQueryCount > 0) {
            $summary['query_count_change'] = round(
                (($totalQueryCount - $totalPreviousQueryCount) / $totalPreviousQueryCount) * 100,
                2
            );
        }

        return $summary;
    }

    private function saveComparison(array $comparison): void
    {
        $filePath = storage_path('performance_comparison.json');
        file_put_contents($filePath, json_encode($comparison, JSON_PRETTY_PRINT));

        echo "\nComparison results saved to: {$filePath}\n";

        // Also generate a human-readable report
        $this->generateComparisonReport($comparison);
    }

    private function generateComparisonReport(array $comparison): void
    {
        $reportPath = storage_path('performance_comparison_report.html');

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Performance Comparison Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .regression { background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .improvement { background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .summary { background-color: #e2e3e5; padding: 15px; border-radius: 5px; margin: 20px 0; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .metric-increase { color: #dc3545; font-weight: bold; }
        .metric-decrease { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>';

        $html .= '<div class="header">';
        $html .= '<h1>Performance Comparison Report</h1>';
        $html .= '<p><strong>Generated:</strong> '.$comparison['timestamp'].'</p>';
        $html .= '<p><strong>Current Baseline:</strong> '.$comparison['current_baseline_date'].'</p>';
        $html .= '<p><strong>Previous Baseline:</strong> '.$comparison['previous_baseline_date'].'</p>';
        $html .= '</div>';

        // Summary section
        $html .= '<div class="summary">';
        $html .= '<h2>Summary</h2>';
        $html .= '<p><strong>Total Endpoints:</strong> '.$comparison['total_endpoints'].'</p>';
        $html .= '<p><strong>Regressions Found:</strong> '.$comparison['regression_count'].'</p>';
        $html .= '<p><strong>Average Response Time:</strong> '.$comparison['summary']['avg_response_time'].'ms ';

        if (0 !== $comparison['summary']['response_time_change']) {
            $changeClass = $comparison['summary']['response_time_change'] > 0 ? 'metric-increase' : 'metric-decrease';
            $changeSymbol = $comparison['summary']['response_time_change'] > 0 ? '+' : '';
            $html .= '<span class="'.$changeClass.'">('.$changeSymbol.$comparison['summary']['response_time_change'].'%)</span>';
        }
        $html .= '</p>';

        $html .= '<p><strong>Average Memory Usage:</strong> '.$comparison['summary']['avg_memory_usage'].'MB ';
        if (0 !== $comparison['summary']['memory_usage_change']) {
            $changeClass = $comparison['summary']['memory_usage_change'] > 0 ? 'metric-increase' : 'metric-decrease';
            $changeSymbol = $comparison['summary']['memory_usage_change'] > 0 ? '+' : '';
            $html .= '<span class="'.$changeClass.'">('.$changeSymbol.$comparison['summary']['memory_usage_change'].'%)</span>';
        }
        $html .= '</p>';
        $html .= '</div>';

        // Regressions section
        if (! empty($comparison['regressions'])) {
            $html .= '<h2>🚨 Performance Regressions</h2>';
            foreach ($comparison['regressions'] as $endpoint => $regressions) {
                $html .= '<div class="regression">';
                $html .= '<h3>'.htmlspecialchars($endpoint).'</h3>';
                foreach ($regressions as $regression) {
                    $html .= '<p><strong>'.$regression['metric'].':</strong> ';
                    $html .= $regression['current_value'].' (was '.$regression['previous_value'].') ';
                    $html .= '<span class="metric-increase">+'.$regression['percentage'].'%</span></p>';
                }
                $html .= '</div>';
            }
        }

        // Improvements section
        if (! empty($comparison['improvements'])) {
            $html .= '<h2>✅ Performance Improvements</h2>';
            foreach ($comparison['improvements'] as $endpoint => $improvements) {
                $html .= '<div class="improvement">';
                $html .= '<h3>'.htmlspecialchars($endpoint).'</h3>';
                foreach ($improvements as $improvement) {
                    $html .= '<p><strong>'.$improvement['metric'].':</strong> ';
                    $html .= $improvement['current_value'].' (was '.$improvement['previous_value'].') ';
                    $html .= '<span class="metric-decrease">-'.$improvement['percentage'].'%</span></p>';
                }
                $html .= '</div>';
            }
        }

        $html .= '</body></html>';

        file_put_contents($reportPath, $html);
        echo "HTML comparison report generated: {$reportPath}\n";
    }
}

// Run the comparison if executed directly
if (PHP_SAPI === 'cli') {
    try {
        $comparator = new PerformanceBaselineComparator();
        $comparison = $comparator->compareBaselines();
        $comparator->printSummary($comparison);

        // Set exit code based on regression detection
        if ($comparison['regression_detected']) {
            echo "\nExiting with error code due to performance regression.\n";

            exit(1);
        }
        echo "\nPerformance comparison completed successfully.\n";

        exit(0);
    } catch (Exception $e) {
        echo 'Error: '.$e->getMessage()."\n";

        exit(1);
    }
}
