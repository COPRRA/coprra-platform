<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
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
    }
}
