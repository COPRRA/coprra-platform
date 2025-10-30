<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCartRequest;
use Darryldecode\Cart\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;

/**
 * @mixin \Darryldecode\Cart\Cart
 * @mixin \Darryldecode\Cart\CartCondition
 */
class CartController extends Controller
{
    public function update(UpdateCartRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var Cart $cartInstance */
        $cartInstance = app('cart');
        $cartInstance->update($validated['id'], [
            'quantity' => [
                'relative' => false,
                'value' => $validated['quantity'],
            ],
        ]);
        Session::flash('success', 'Cart updated!');

        return redirect()->route('cart.index');
    }
}
