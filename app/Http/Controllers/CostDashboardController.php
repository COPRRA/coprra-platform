<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AICostLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class CostDashboardController extends Controller
{
    public function index(): View
    {
        try {
            // Check if the table exists before querying
            $tableExists = DB::getSchemaBuilder()->hasTable('a_i_cost_logs');
            
            if (!$tableExists) {
                // Return empty data if table doesn't exist
                $today = (object) ['total' => 0, 'tokens' => 0];
                $thisMonth = 0.0;
                $thisWeek = collect([]);
                
                return view('dashboard.costs', compact('today', 'thisMonth', 'thisWeek'))
                    ->with('message', 'Cost tracking is not yet configured. The cost dashboard will be available once AI cost logging is enabled.');
            }

            // Get today's cost summary
            $today = AICostLog::whereDate('created_at', today())
                ->selectRaw('SUM(estimated_cost) as total, SUM(tokens_used) as tokens')
                ->first()
            ;

            // Get this month's total cost
            $thisMonth = (float) AICostLog::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('estimated_cost')
            ;

            // Get this week's cost breakdown
            $thisWeek = AICostLog::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(estimated_cost) as cost'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
            ;

            return view('dashboard.costs', compact('today', 'thisMonth', 'thisWeek'));
        } catch (\Exception $e) {
            // Gracefully handle any database errors
            $today = (object) ['total' => 0, 'tokens' => 0];
            $thisMonth = 0.0;
            $thisWeek = collect([]);
            
            return view('dashboard.costs', compact('today', 'thisMonth', 'thisWeek'))
                ->with('error', 'Unable to load cost data. Please contact support if this issue persists.');
        }
    }
}
