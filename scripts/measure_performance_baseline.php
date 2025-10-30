<?php

declare(strict_types=1);

/**
 * Performance Baseline Measurement Script.
 *
 * This script measures the current performance of critical endpoints
 * and establishes baseline measurements for regression detection.
 */

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

class measure_performance_baseline
{
    private Application $app;
    private array $measurements = [];
    private array $endpoints;
    private int $iterations;

    public function __construct(int $iterations = 10)
    {
        $this->app = require __DIR__.'/../bootstrap/app.php';
        $this->app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        $this->iterations = $iterations;
        $this->setupEndpoints();
    }

    public function measureBaselines(): array
    {
        echo "Starting performance baseline measurement...\n";
        echo "Iterations per endpoint: {$this->iterations}\n\n";

        foreach ($this->endpoints as $name => $config) {
            echo "Measuring: {$name}\n";

            // Setup test data if needed
            if (isset($config['setup'])) {
                $this->{$config['setup']}();
            }

            $measurements = $this->measureEndpoint($config);
            $this->measurements[$name] = $measurements;

            echo sprintf(
                "  Avg Response Time: %.2fms\n",
                $measurements['avg_response_time']
            );
            echo sprintf(
                "  Avg Memory Usage: %.2fMB\n",
                $measurements['avg_memory_usage']
            );
            echo sprintf(
                "  Avg Query Count: %d\n\n",
                $measurements['avg_query_count']
            );
        }

        return $this->measurements;
    }

    public function saveBaselines(array $measurements): void
    {
        $baselineData = [
            'last_updated' => date('Y-m-d H:i:s'),
            'environment' => app()->environment(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'measurements' => $measurements,
        ];

        $filePath = storage_path('performance_baselines.json');
        file_put_contents($filePath, json_encode($baselineData, JSON_PRETTY_PRINT));

        echo "Baselines saved to: {$filePath}\n";
    }

    public function generateReport(array $measurements): void
    {
        $reportPath = storage_path('performance_baseline_report.html');

        $html = $this->generateHtmlReport($measurements);
        file_put_contents($reportPath, $html);

        echo "HTML report generated: {$reportPath}\n";
    }

    private function setupEndpoints(): void
    {
        $this->endpoints = [
            // Authentication endpoints
            'POST /api/auth/login' => [
                'method' => 'POST',
                'uri' => '/api/auth/login',
                'data' => ['email' => 'test@example.com', 'password' => 'password'],
                'setup' => 'createTestUser',
            ],
            'POST /api/auth/register' => [
                'method' => 'POST',
                'uri' => '/api/auth/register',
                'data' => [
                    'name' => 'Test User',
                    'email' => 'newuser@example.com',
                    'password' => 'password',
                    'password_confirmation' => 'password',
                ],
            ],

            // Product endpoints
            'GET /api/products' => [
                'method' => 'GET',
                'uri' => '/api/products',
                'setup' => 'createTestProducts',
            ],
            'GET /api/products/{id}' => [
                'method' => 'GET',
                'uri' => '/api/products/1',
                'setup' => 'createTestProducts',
            ],

            // Search endpoints
            'GET /api/search' => [
                'method' => 'GET',
                'uri' => '/api/search?q=test',
                'setup' => 'createTestProducts',
            ],
            'POST /api/price-search' => [
                'method' => 'POST',
                'uri' => '/api/price-search',
                'data' => ['product_name' => 'Test Product'],
                'setup' => 'createTestProducts',
            ],

            // System endpoints
            'GET /api/system/health' => [
                'method' => 'GET',
                'uri' => '/api/system/health',
            ],
            'GET /api/system/info' => [
                'method' => 'GET',
                'uri' => '/api/system/info',
            ],

            // Category and Brand endpoints
            'GET /api/categories' => [
                'method' => 'GET',
                'uri' => '/api/categories',
                'setup' => 'createTestCategories',
            ],
            'GET /api/brands' => [
                'method' => 'GET',
                'uri' => '/api/brands',
                'setup' => 'createTestBrands',
            ],
        ];
    }

    private function measureEndpoint(array $config): array
    {
        $responseTimes = [];
        $memoryUsages = [];
        $queryCounts = [];

        for ($i = 0; $i < $this->iterations; ++$i) {
            // Clear any previous state
            $this->clearState();

            // Start measurements
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);

            // Enable query logging
            DB::enableQueryLog();
            DB::flushQueryLog();

            try {
                // Make the request
                $response = $this->makeRequest($config);

                // Record measurements
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                $queryCount = count(DB::getQueryLog());

                $responseTimes[] = ($endTime - $startTime) * 1000; // Convert to ms
                $memoryUsages[] = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
                $queryCounts[] = $queryCount;
            } catch (Exception $e) {
                echo "  Error in iteration {$i}: ".$e->getMessage()."\n";

                continue;
            }

            // Small delay between iterations
            usleep(100000); // 100ms
        }

        return [
            'avg_response_time' => array_sum($responseTimes) / count($responseTimes),
            'min_response_time' => min($responseTimes),
            'max_response_time' => max($responseTimes),
            'avg_memory_usage' => array_sum($memoryUsages) / count($memoryUsages),
            'min_memory_usage' => min($memoryUsages),
            'max_memory_usage' => max($memoryUsages),
            'avg_query_count' => array_sum($queryCounts) / count($queryCounts),
            'min_query_count' => min($queryCounts),
            'max_query_count' => max($queryCounts),
            'iterations' => count($responseTimes),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    private function makeRequest(array $config): mixed
    {
        $request = Request::create(
            $config['uri'],
            $config['method'],
            $config['data'] ?? [],
            [], // cookies
            [], // files
            ['HTTP_ACCEPT' => 'application/json']
        );

        return $this->app->handle($request);
    }

    private function clearState(): void
    {
        // Clear caches
        Cache::flush();

        // Clear query log
        DB::flushQueryLog();

        // Force garbage collection
        gc_collect_cycles();
    }

    private function createTestUser(): void
    {
        // Create a test user if it doesn't exist
        try {
            User::firstOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => bcrypt('password'),
                ]
            );
        } catch (Exception $e) {
            // User might already exist, continue
        }
    }

    private function createTestProducts(): void
    {
        // Create test products if they don't exist
        try {
            if (Product::count() < 10) {
                Product::factory(10)->create();
            }
        } catch (Exception $e) {
            // Products might already exist, continue
        }
    }

    private function createTestCategories(): void
    {
        // Create test categories if they don't exist
        try {
            if (Category::count() < 5) {
                Category::factory(5)->create();
            }
        } catch (Exception $e) {
            // Categories might already exist, continue
        }
    }

    private function createTestBrands(): void
    {
        // Create test brands if they don't exist
        try {
            if (Brand::count() < 5) {
                Brand::factory(5)->create();
            }
        } catch (Exception $e) {
            // Brands might already exist, continue
        }
    }

    private function generateHtmlReport(array $measurements): string
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>Performance Baseline Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .metric { margin: 10px 0; }
        .good { color: green; }
        .warning { color: orange; }
        .critical { color: red; }
    </style>
</head>
<body>
    <h1>Performance Baseline Report</h1>
    <p>Generated on: '.date('Y-m-d H:i:s').'</p>

    <table>
        <thead>
            <tr>
                <th>Endpoint</th>
                <th>Avg Response Time (ms)</th>
                <th>Avg Memory Usage (MB)</th>
                <th>Avg Query Count</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($measurements as $endpoint => $data) {
            $status = $this->getStatusClass($data);
            $html .= sprintf(
                '<tr>
                    <td>%s</td>
                    <td class="%s">%.2f</td>
                    <td class="%s">%.2f</td>
                    <td class="%s">%d</td>
                    <td class="%s">%s</td>
                </tr>',
                htmlspecialchars($endpoint),
                $status,
                $data['avg_response_time'],
                $status,
                $data['avg_memory_usage'],
                $status,
                $data['avg_query_count'],
                $status,
                ucfirst($status)
            );
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    private function getStatusClass(array $data): string
    {
        // Simple status classification based on response time
        if ($data['avg_response_time'] < 300) {
            return 'good';
        }
        if ($data['avg_response_time'] < 1000) {
            return 'warning';
        }

        return 'critical';
    }
}

// Run the baseline measurement
if (PHP_SAPI === 'cli') {
    $measurer = new PerformanceBaselineMeasurer(5); // 5 iterations for faster execution
    $measurements = $measurer->measureBaselines();
    $measurer->saveBaselines($measurements);
    $measurer->generateReport($measurements);

    echo "\nPerformance baseline measurement completed!\n";
}
