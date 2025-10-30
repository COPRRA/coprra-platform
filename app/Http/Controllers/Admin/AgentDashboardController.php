<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AI\Services\AgentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentDashboardController extends Controller
{
    private AgentLifecycleService $lifecycleService;

    public function __construct(AgentLifecycleService $lifecycleService)
    {
        $this->lifecycleService = $lifecycleService;
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Display the main agent dashboard.
     */
    public function index(): View
    {
        return view('admin.agent-dashboard');
    }

    /**
     * Get dashboard data (JSON API).
     */
    public function getDashboardData(): JsonResponse
    {
        try {
            $data = $this->collectDashboardData();

            return response()->json([
                'success' => true,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard data',
            ], 500);
        }
    }

    /**
     * Stream real-time dashboard updates via Server-Sent Events.
     */
    public function streamUpdates(): StreamedResponse
    {
        return response()->stream(function () {
            $lastUpdate = 0;

            while (true) {
                // Check if client is still connected
                if (connection_aborted()) {
                    break;
                }

                $currentTime = time();

                // Send updates every 5 seconds
                if ($currentTime - $lastUpdate >= 5) {
                    try {
                        $data = $this->collectDashboardData();

                        echo 'data: '.json_encode([
                            'type' => 'dashboard_update',
                            'data' => $data,
                            'timestamp' => now()->toISOString(),
                        ])."\n\n";

                        ob_flush();
                        flush();

                        $lastUpdate = $currentTime;
                    } catch (\Exception $e) {
                        Log::error('SSE stream error', [
                            'error' => $e->getMessage(),
                        ]);

                        echo 'data: '.json_encode([
                            'type' => 'error',
                            'message' => 'Failed to get updates',
                        ])."\n\n";

                        ob_flush();
                        flush();
                    }
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get detailed agent information.
     */
    public function getAgentDetails(string $agentId): JsonResponse
    {
        try {
            $agent = $this->lifecycleService->getAgentStatus($agentId);

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Agent not found',
                ], 404);
            }

            // Get additional details
            $metrics = $this->getAgentMetrics($agentId);
            $logs = $this->getAgentLogs($agentId, 10);
            $config = $this->getAgentConfiguration($agentId);

            return response()->json([
                'success' => true,
                'data' => [
                    'agent' => $agent,
                    'metrics' => $metrics,
                    'logs' => $logs,
                    'configuration' => $config,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get agent details', [
                'agent_id' => $agentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load agent details',
            ], 500);
        }
    }

    /**
     * Get system-wide metrics.
     */
    public function getSystemMetrics(): JsonResponse
    {
        try {
            $metrics = [
                'throughput' => $this->getThroughputMetrics(),
                'response_times' => $this->getResponseTimeMetrics(),
                'error_rates' => $this->getErrorRateMetrics(),
                'resource_usage' => $this->getResourceUsageMetrics(),
            ];

            return response()->json([
                'success' => true,
                'data' => $metrics,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get system metrics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load system metrics',
            ], 500);
        }
    }

    /**
     * Search and filter agents.
     */
    public function searchAgents(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q', '');
            $status = $request->get('status', '');
            $limit = min($request->get('limit', 20), 100);
            $offset = $request->get('offset', 0);

            $agents = $this->lifecycleService->getAllAgents();

            // Filter by search query
            if ($query) {
                $agents = array_filter($agents, static function ($agent) use ($query) {
                    return false !== stripos($agent['id'], $query)
                           || false !== stripos($agent['type'] ?? '', $query);
                });
            }

            // Filter by status
            if ($status) {
                $agents = array_filter($agents, static function ($agent) use ($status) {
                    return $agent['status'] === $status;
                });
            }

            // Apply pagination
            $total = \count($agents);
            $agents = \array_slice($agents, $offset, $limit);

            return response()->json([
                'success' => true,
                'data' => [
                    'agents' => array_values($agents),
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to search agents', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to search agents',
            ], 500);
        }
    }

    /**
     * Collect comprehensive dashboard data.
     */
    private function collectDashboardData(): array
    {
        // Cache dashboard data for 30 seconds to reduce load
        return Cache::remember('agent_dashboard_data', 30, function () {
            $agents = $this->lifecycleService->getAllAgents();

            // Calculate statistics
            $stats = [
                'total' => \count($agents),
                'active' => 0,
                'paused' => 0,
                'failed' => 0,
                'initializing' => 0,
            ];

            foreach ($agents as $agent) {
                $status = $agent['status'] ?? 'unknown';
                if (isset($stats[$status])) {
                    ++$stats[$status];
                }
            }

            // Get recent activity
            $recentActivity = $this->getRecentActivity();

            // Get system health
            $systemHealth = $this->getSystemHealth();

            return [
                'statistics' => $stats,
                'agents' => $agents,
                'recent_activity' => $recentActivity,
                'system_health' => $systemHealth,
            ];
        });
    }

    /**
     * Get agent performance metrics.
     */
    private function getAgentMetrics(string $agentId): array
    {
        // This would typically come from a metrics storage system
        // For now, return mock data structure
        return [
            'cpu_usage' => [
                'current' => rand(20, 80),
                'average_24h' => rand(30, 60),
                'peak_24h' => rand(60, 95),
            ],
            'memory_usage' => [
                'current' => rand(1024, 4096), // MB
                'average_24h' => rand(1500, 3000),
                'peak_24h' => rand(3000, 4096),
            ],
            'request_count' => [
                'last_hour' => rand(100, 1000),
                'last_24h' => rand(2000, 20000),
                'total' => rand(50000, 500000),
            ],
            'response_times' => [
                'average' => rand(100, 500), // ms
                'p95' => rand(500, 1000),
                'p99' => rand(1000, 2000),
            ],
        ];
    }

    /**
     * Get agent logs.
     */
    private function getAgentLogs(string $agentId, int $limit = 50): array
    {
        // This would typically come from a logging system
        // For now, return mock log entries
        $logs = [];
        $levels = ['INFO', 'DEBUG', 'WARN', 'ERROR'];

        for ($i = 0; $i < $limit; ++$i) {
            $logs[] = [
                'timestamp' => now()->subMinutes($i * 2)->toISOString(),
                'level' => $levels[array_rand($levels)],
                'message' => $this->generateMockLogMessage(),
                'context' => [],
            ];
        }

        return $logs;
    }

    /**
     * Get agent configuration.
     */
    private function getAgentConfiguration(string $agentId): array
    {
        // This would typically come from configuration storage
        return [
            'max_memory' => '4GB',
            'timeout' => 30,
            'retry_attempts' => 3,
            'priority' => 'high',
            'queue' => 'default',
            'port' => 8080,
            'auto_restart' => true,
            'health_check_interval' => 30,
        ];
    }

    /**
     * Get recent system activity.
     */
    private function getRecentActivity(): array
    {
        return [
            [
                'timestamp' => now()->subMinutes(2)->toISOString(),
                'type' => 'agent_started',
                'message' => 'Agent agent-001 started successfully',
                'agent_id' => 'agent-001',
            ],
            [
                'timestamp' => now()->subMinutes(5)->toISOString(),
                'type' => 'agent_failed',
                'message' => 'Agent agent-003 failed with timeout error',
                'agent_id' => 'agent-003',
            ],
            [
                'timestamp' => now()->subMinutes(10)->toISOString(),
                'type' => 'system_alert',
                'message' => 'High memory usage detected across multiple agents',
                'agent_id' => null,
            ],
        ];
    }

    /**
     * Get system health indicators.
     */
    private function getSystemHealth(): array
    {
        return [
            'overall_status' => 'healthy',
            'cpu_usage' => rand(30, 70),
            'memory_usage' => rand(40, 80),
            'disk_usage' => rand(20, 60),
            'network_latency' => rand(10, 50),
            'active_connections' => rand(50, 200),
        ];
    }

    /**
     * Get throughput metrics.
     */
    private function getThroughputMetrics(): array
    {
        $data = [];
        for ($i = 23; $i >= 0; --$i) {
            $data[] = [
                'timestamp' => now()->subHours($i)->format('H:i'),
                'requests' => rand(100, 1000),
            ];
        }

        return $data;
    }

    /**
     * Get response time metrics.
     */
    private function getResponseTimeMetrics(): array
    {
        return [
            'average' => rand(200, 400),
            'p50' => rand(150, 300),
            'p95' => rand(400, 800),
            'p99' => rand(800, 1500),
            'max' => rand(1500, 3000),
        ];
    }

    /**
     * Get error rate metrics.
     */
    private function getErrorRateMetrics(): array
    {
        return [
            'total_errors' => rand(10, 100),
            'error_rate' => rand(1, 5) / 10, // 0.1% - 0.5%
            'critical_errors' => rand(0, 5),
            'by_type' => [
                'timeout' => rand(5, 30),
                'connection' => rand(2, 15),
                'validation' => rand(3, 20),
                'internal' => rand(1, 10),
            ],
        ];
    }

    /**
     * Get resource usage metrics.
     */
    private function getResourceUsageMetrics(): array
    {
        return [
            'cpu' => [
                'current' => rand(30, 70),
                'average' => rand(40, 60),
                'peak' => rand(70, 95),
            ],
            'memory' => [
                'current' => rand(4, 12), // GB
                'average' => rand(6, 10),
                'peak' => rand(10, 16),
            ],
            'disk' => [
                'used' => rand(100, 500), // GB
                'available' => rand(500, 1000),
                'usage_percent' => rand(20, 60),
            ],
        ];
    }

    /**
     * Generate mock log message.
     */
    private function generateMockLogMessage(): string
    {
        $messages = [
            'Agent initialized successfully',
            'Processing request #%d',
            'High memory usage detected',
            'Request completed in %dms',
            'Connection established to external service',
            'Cache miss for key: %s',
            'Heartbeat sent successfully',
            'Configuration updated',
            'Error handling request: timeout',
            'Agent paused due to resource constraints',
        ];

        $message = $messages[array_rand($messages)];

        // Replace placeholders with random values
        return \sprintf($message, rand(1000, 9999), rand(100, 2000), 'cache_key_'.rand(1, 100));
    }
}
