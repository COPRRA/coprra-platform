<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml for SEO';

    public function handle()
    {
        $this->info('🗺️  Generating sitemap.xml...');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= $this->addUrl('https://coprra.com/', '1.0', 'daily');

        // Static pages
        $xml .= $this->addUrl('https://coprra.com/products', '0.9', 'daily');
        $xml .= $this->addUrl('https://coprra.com/brands', '0.8', 'weekly');
        $xml .= $this->addUrl('https://coprra.com/categories', '0.8', 'weekly');
        $xml .= $this->addUrl('https://coprra.com/compare', '0.7', 'weekly');

        // Products
        $products = Product::where('is_active', true)->get(['slug', 'updated_at']);
        foreach ($products as $product) {
            $xml .= $this->addUrl(
                'https://coprra.com/products/' . $product->slug,
                '0.8',
                'weekly',
                $product->updated_at->toAtomString()
            );
        }
        $this->info('✅ Added ' . $products->count() . ' products');

        // Brands
        $brands = Brand::has('products')->get(['slug', 'updated_at']);
        foreach ($brands as $brand) {
            $xml .= $this->addUrl(
                'https://coprra.com/brands/' . $brand->slug,
                '0.7',
                'weekly',
                $brand->updated_at->toAtomString()
            );
        }
        $this->info('✅ Added ' . $brands->count() . ' brands');

        // Categories
        $categories = Category::has('products')->get(['id', 'updated_at']);
        foreach ($categories as $category) {
            $xml .= $this->addUrl(
                'https://coprra.com/categories/' . $category->id,
                '0.7',
                'weekly',
                $category->updated_at->toAtomString()
            );
        }
        $this->info('✅ Added ' . $categories->count() . ' categories');

        $xml .= '</urlset>';

        // Save to public directory
        file_put_contents(public_path('sitemap.xml'), $xml);

        $this->info('');
        $this->info('✅ Sitemap generated successfully!');
        $this->info('📍 Location: public/sitemap.xml');
        $this->info('🌐 URL: https://coprra.com/sitemap.xml');

        return 0;
    }

    private function addUrl($loc, $priority = '0.5', $changefreq = 'weekly', $lastmod = null)
    {
        $xml = '<url>';
        $xml .= '<loc>' . htmlspecialchars($loc) . '</loc>';
        if ($lastmod) {
            $xml .= '<lastmod>' . $lastmod . '</lastmod>';
        }
        $xml .= '<changefreq>' . $changefreq . '</changefreq>';
        $xml .= '<priority>' . $priority . '</priority>';
        $xml .= '</url>';
        return $xml;
    }
}
