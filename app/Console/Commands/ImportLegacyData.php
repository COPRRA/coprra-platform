<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductModel;
use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import {--path=} {--dry} {--chunk=500}';
    protected $description = 'Import legacy JSON exports into the database with idempotent, transactional, validated operations.';

    public function handle()
    {
        $path = rtrim($this->option('path') ?? '', \DIRECTORY_SEPARATOR);
        $dry = (bool) $this->option('dry');
        $chunk = (int) $this->option('chunk');

        if (! $path || ! is_dir($path)) {
            $this->error('Invalid --path to JSON exports.');

            return 1;
        }

        $tables = [
            ['file' => 'languages.json', 'model' => Language::class, 'unique' => ['code']],
            ['file' => 'currencies.json', 'model' => Currency::class, 'unique' => ['code']],
            ['file' => 'stores.json', 'model' => Store::class, 'unique' => ['name']],
            ['file' => 'brands.json', 'model' => Brand::class, 'unique' => ['name']],
            ['file' => 'categories.json', 'model' => Category::class, 'unique' => ['name']],
            ['file' => 'product_models.json', 'model' => ProductModel::class, 'unique' => ['name', 'brand_id', 'category_id']],
            ['file' => 'users.json', 'model' => User::class, 'unique' => ['email']],
            ['file' => 'products.json', 'model' => Product::class, 'unique' => ['sku']],
        ];

        foreach ($tables as $tbl) {
            $file = $path.\DIRECTORY_SEPARATOR.$tbl['file'];
            if (! file_exists($file)) {
                $this->warn("Missing file: {$tbl['file']} — skipping.");

                continue;
            }

            $payload = json_decode(file_get_contents($file), true);
            if (! \is_array($payload)) {
                $this->error("Invalid JSON in {$tbl['file']}");

                return 1;
            }

            $count = \count($payload);
            $this->info("Processing {$tbl['file']} ({$count} records)");

            $validator = static function (array $row) use ($tbl) {
                $rules = [];
                $modelClass = $tbl['model'];

                switch ($modelClass) {
                    case User::class:
                        $rules = [
                            'email' => 'required|string',
                            'name' => 'nullable|string',
                        ];

                        break;

                    case Category::class:
                    case Brand::class:
                    case Store::class:
                        $rules = ['name' => 'required|string'];

                        break;

                    case ProductModel::class:
                        $rules = ['name' => 'required|string', 'brand_id' => 'nullable|integer', 'category_id' => 'nullable|integer'];

                        break;

                    case Product::class:
                        $rules = [
                            'name' => 'required|string',
                            'price' => 'nullable|numeric|min:0',
                            'brand_id' => 'nullable|integer',
                            'category_id' => 'nullable|integer',
                            'store_id' => 'nullable|integer',
                            'currency_id' => 'nullable|integer',
                            'product_model_id' => 'nullable|integer',
                        ];

                        break;

                    default:
                        $rules = [];
                }
                $v = Validator::make($row, $rules);

                return ! $v->fails();
            };

            $uniqueKeys = $tbl['unique'];
            $modelClass = $tbl['model'];

            $import = static function () use ($payload, $modelClass, $validator, $uniqueKeys, $chunk) {
                $created = 0;
                $updated = 0;
                $skipped = 0;
                $errors = 0;
                foreach (array_chunk($payload, $chunk) as $batch) {
                    DB::transaction(static function () use ($batch, $modelClass, $validator, $uniqueKeys, &$created, &$updated, &$skipped, &$errors) {
                        foreach ($batch as $row) {
                            if (! $validator($row)) {
                                ++$skipped;
                                Log::warning('Validation failed', ['model' => $modelClass, 'row' => $row]);

                                continue;
                            }
                            $where = [];
                            foreach ($uniqueKeys as $key) {
                                if (\array_key_exists($key, $row) && null !== $row[$key]) {
                                    $where[$key] = $row[$key];
                                }
                            }
                            if (empty($where)) {
                                if (\array_key_exists('id', $row)) {
                                    $where = ['id' => $row['id']];
                                } else {
                                    ++$skipped;
                                    Log::warning('No unique key for idempotent import', ['model' => $modelClass, 'row' => $row]);

                                    continue;
                                }
                            }
                            $attributes = $row;
                            $model = $modelClass::where($where)->first();
                            if ($model) {
                                $model->fill($attributes);
                                $model->save();
                                ++$updated;
                            } else {
                                $modelClass::create($attributes);
                                ++$created;
                            }
                        }
                    });
                }

                return [$created, $updated, $skipped, $errors];
            };

            if ($dry) {
                $this->info('Dry run: validating only, no writes.');
                $skipped = 0;
                $validated = 0;
                foreach ($payload as $row) {
                    if ($validator($row)) {
                        ++$validated;
                    } else {
                        ++$skipped;
                    }
                }
                $this->info("Validated: {$validated}, Skipped: {$skipped}");

                continue;
            }

            [$created, $updated, $skipped, $errors] = $import();
            $this->info("Created: {$created}, Updated: {$updated}, Skipped: {$skipped}, Errors: {$errors}");
        }

        $this->info('Import completed.');

        return 0;
    }
}
