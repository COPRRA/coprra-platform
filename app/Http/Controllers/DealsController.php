<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class DealsController extends Controller
{
    public function index(): View
    {
        try {
            $deals = Product::where('is_active', true)
                ->with(['brand', 'category'])
                ->latest()
                ->take(12)
                ->get()
            ;
        } catch (\Exception $e) {
            $deals = collect([]);
        }

        return view('deals.index', compact('deals'));
    }
}
