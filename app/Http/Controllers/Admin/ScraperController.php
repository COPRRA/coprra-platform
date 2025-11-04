<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessScrapingJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ScraperController extends Controller
{
    public function index(): View
    {
        $logFile = storage_path('logs/scraper.log');
        $recentLogs = [];

        if (file_exists($logFile)) {
            $lines = file($logFile);
            $recentLogs = \array_slice(array_reverse($lines), 0, 50);
        }

        return view('admin.scraper.index', compact('recentLogs'));
    }

    public function startScraping(Request $request): RedirectResponse
    {
        $request->validate([
            'urls' => 'required|string',
        ]);

        $urls = array_filter(
            array_map('trim', explode("\n", $request->urls)),
            static function ($url) {
                return ! empty($url) && filter_var($url, \FILTER_VALIDATE_URL);
            }
        );

        if (empty($urls)) {
            return back()->with('error', 'No valid URLs provided.');
        }

        $batchId = uniqid('batch_');
        Log::channel('scraper')->info("🚀 NEW BATCH: {$batchId} - ".\count($urls).' URLs');

        $jobsDispatched = 0;
        foreach ($urls as $index => $url) {
            try {
                ProcessScrapingJob::dispatch($url, $batchId, $index + 1);
                ++$jobsDispatched;
                Log::channel('scraper')->info('✅ Job #'.($index + 1).' dispatched');
            } catch (\Exception $e) {
                Log::channel('scraper')->error('❌ Failed: '.$e->getMessage());
            }
        }

        return back()->with('success', "Scraping started! {$jobsDispatched} jobs dispatched.");
    }

    public function getLogs(): JsonResponse
    {
        $logFile = storage_path('logs/scraper.log');
        $logs = [];

        if (file_exists($logFile)) {
            $lines = file($logFile);
            $logs = \array_slice(array_reverse($lines), 0, 100);
        }

        return response()->json(['logs' => $logs]);
    }

    public function clearLogs(): RedirectResponse
    {
        $logFile = storage_path('logs/scraper.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return back()->with('success', 'Logs cleared successfully.');
    }
}
