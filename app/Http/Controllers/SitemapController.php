<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        try {
            $products = Product::where('is_active', true)->get();
            $categories = Category::all();
            $brands = Brand::all();
        } catch (\Exception $e) {
            $products = collect([]);
            $categories = collect([]);
            $brands = collect([]);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= '<url><loc>https://coprra.com</loc><priority>1.0</priority></url>';

        // Products
        foreach ($products as $product) {
            $slug = $product->slug ?? $product->id;
            $xml .= "<url><loc>https://coprra.com/products/{$slug}</loc><priority>0.8</priority></url>";
        }

        // Categories
        foreach ($categories as $category) {
            $slug = $category->slug ?? $category->id;
            $xml .= "<url><loc>https://coprra.com/categories/{$slug}</loc><priority>0.7</priority></url>";
        }

        // Static pages
        $staticPages = [
            ['url' => 'products', 'priority' => '0.9'],
            ['url' => 'categories', 'priority' => '0.9'],
            ['url' => 'brands', 'priority' => '0.8'],
            ['url' => 'deals', 'priority' => '0.9'],
            ['url' => 'blog', 'priority' => '0.7'],
            ['url' => 'compare', 'priority' => '0.6'],
            ['url' => 'about', 'priority' => '0.5'],
            ['url' => 'contact', 'priority' => '0.5'],
        ];

        foreach ($staticPages as $page) {
            $xml .= "<url><loc>https://coprra.com/{$page['url']}</loc><priority>{$page['priority']}</priority></url>";
        }

        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
