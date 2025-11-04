<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComparisonController extends Controller
{
    public function index(Request $request): View
    {
        $productIds = $request->query('products', []);
        $products = Product::whereIn('id', $productIds)->take(4)->get();

        return view('comparison.index', compact('products'));
    }
}
