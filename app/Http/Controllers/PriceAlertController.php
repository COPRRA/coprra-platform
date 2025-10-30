<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePriceAlertRequest;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceAlertController extends Controller
{
    private const UNAUTHORIZED_MESSAGE = 'Unauthorized action.';

    public function create(Request $request): View
    {
        $product = null;
        if ($request->has('product_id')) {
            $product = app(Product::class)->findOrFail($request->input('product_id'));
        }

        return view('price-alerts.create', ['product' => $product]);
    }

    /**
     * Display the specified price alert.
     */
    public function show(PriceAlert $priceAlert, Guard $auth): View
    {
        if ($priceAlert->user_id !== $auth->id()) {
            abort(403, self::UNAUTHORIZED_MESSAGE);
        }

        // Eager load relationships to prevent N+1 queries
        $priceAlert->load(['product.priceOffers']);

        return view('price-alerts.show', compact('priceAlert'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePriceAlertRequest $request, PriceAlert $priceAlert, Guard $auth): RedirectResponse
    {
        if ($priceAlert->user_id !== $auth->id()) {
            abort(403, self::UNAUTHORIZED_MESSAGE);
        }

        $priceAlert->update([
            'target_price' => $request->input('target_price'),
            'repeat_alert' => $request->boolean('repeat_alert'),
        ]);

        return redirect()->route('price-alerts.index')
            ->with('success', 'Price alert updated successfully!')
        ;
    }
}
