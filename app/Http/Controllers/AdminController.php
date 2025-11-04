<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminController extends Controller
{
    public function dashboard(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $stats = [
            'users' => User::count(),
            'products' => Product::count(),
            'stores' => Store::count(),
            'categories' => Category::count(),
        ];

        $recentUsers = User::latest()->take(5)->get();
        $recentProducts = Product::latest()->take(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentProducts' => $recentProducts,
        ]);
    }

    public function users(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $users = User::latest()->paginate(15);

        return view('admin.users', [
            'users' => $users,
        ]);
    }

    public function products(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $products = Product::latest()->paginate(20);

        return view('admin.products', [
            'products' => $products,
        ]);
    }

    public function brands(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $brands = Brand::latest()->paginate(20);

        return view('admin.brands', [
            'brands' => $brands,
        ]);
    }

    public function categories(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $categories = Category::latest()->paginate(20);

        return view('admin.categories', [
            'categories' => $categories,
        ]);
    }

    public function stores(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('admin')) {
            return redirect()->route('home');
        }

        $stores = Store::latest()->paginate(20);

        return view('admin.stores', [
            'stores' => $stores,
        ]);
    }

    public function toggleUserAdmin(User $user): RedirectResponse
    {
        $acting = auth()->user();
        if (! $acting || ! method_exists($acting, 'hasRole') || ! $acting->hasRole('admin')) {
            return redirect()->route('home');
        }

        $user->is_admin = ! (bool) $user->is_admin;
        if ($user->is_admin) {
            $user->role = 'admin';
        } elseif ($user->role === 'admin') {
            $user->role = 'user';
        }

        $user->save();

        return redirect()->route('admin.users')->with('status', 'User admin status updated.');
    }
}
