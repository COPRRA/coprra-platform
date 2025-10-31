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
    }
}
