<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    /**
     * Display a listing of brands.
     */
    public function index(Request $request): View
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $brands = Brand::query()
                ->active()
                ->orderBy('name')
                ->paginate($perPage);

            return view('brands.index', compact('brands'));
        } catch (\Throwable $e) {
            Log::error('Brands index failure', [
                'exception' => $e->getMessage(),
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }
    /**
     * Show the form for creating a new brand.
     */
    public function create(): View
    {
        return view('brands.create');
    }

    /**
     * Update the specified brand in storage.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $brand = Brand::find($id);
            if (! $brand) {
                abort(404);
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', 'unique:brands,slug,'.$brand->id],
                'description' => ['nullable', 'string', 'max:1000'],
                'website_url' => ['nullable', 'url', 'max:500'],
                'logo_url' => ['nullable', 'url', 'max:500'],
            ]);

            $brand->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
                'website_url' => $validated['website_url'] ?? null,
                'logo_url' => $validated['logo_url'] ?? null,
            ]);

            return redirect('/brands');
        } catch (\Throwable $e) {
            Log::error('Brand update failure', [
                'exception' => $e->getMessage(),
                'id' => $id,
                'url' => $request->fullUrl(),
                'trace' => $e->getTraceAsString(),
            ]);
            abort(500);
        }
    }
}
