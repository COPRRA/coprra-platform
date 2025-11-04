<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sitemap.xml for static pages and dynamic content (products, categories).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseUrl = rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');

        $sitemap = Sitemap::create();

        // Static pages
        $sitemap->add(
            Url::create($baseUrl.'/')
                ->setLastModificationDate(Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        $sitemap->add(
            Url::create($baseUrl.'/products')
                ->setLastModificationDate(Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.9)
        );

        $sitemap->add(
            Url::create($baseUrl.'/categories')
                ->setLastModificationDate(Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.6)
        );

        $sitemap->add(
            Url::create($baseUrl.'/contact')
                ->setLastModificationDate(Carbon::now())
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.3)
        );

        // Dynamic products
        Product::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->select(['slug', 'updated_at'])
            ->chunk(500, function ($products) use ($sitemap, $baseUrl): void {
                foreach ($products as $product) {
                    $slug = (string) $product->slug;
                    if ('' === $slug) {
                        continue;
                    }
                    $lastmod = $product->updated_at instanceof Carbon
                        ? Carbon::create($product->updated_at)
                        : Carbon::now();

                    $sitemap->add(
                        Url::create($baseUrl.'/products/'.Str::of($slug)->ltrim('/'))
                            ->setLastModificationDate($lastmod)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                            ->setPriority(0.8)
                    );
                }
            });

        // Dynamic categories
        Category::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->select(['slug', 'updated_at'])
            ->chunk(500, function ($categories) use ($sitemap, $baseUrl): void {
                foreach ($categories as $category) {
                    $slug = (string) $category->slug;
                    if ('' === $slug) {
                        continue;
                    }
                    $lastmod = $category->updated_at instanceof Carbon
                        ? Carbon::create($category->updated_at)
                        : Carbon::now();

                    $sitemap->add(
                        Url::create($baseUrl.'/categories/'.Str::of($slug)->ltrim('/'))
                            ->setLastModificationDate($lastmod)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.5)
                    );
                }
            });

        // Write sitemap to public folder
        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        $this->info("Sitemap generated: {$path}");

        return self::SUCCESS;
    }
}

