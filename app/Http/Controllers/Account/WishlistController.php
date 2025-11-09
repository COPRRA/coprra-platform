<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Display the authenticated user's wishlist page.
     */
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $wishlistProducts = $user->wishlist()
            ->with([
                'brand:id,name,slug',
                'category:id,name,slug',
            ])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12);

        $compareIds = $request->session()->get('compare', []);

        return view('account.wishlist', [
            'products' => $wishlistProducts,
            'compareIds' => $compareIds,
        ]);
    }
}
