<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class DeployController extends Controller
{
    /**
     * Temporary deployment endpoint - will be removed after use.
     */
    public function deployAndRunScraper(Request $request)
    {
        // Security check
        if ('COPRRA_DEPLOY_2025_TEMP' !== $request->input('secret')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $output = [];

        try {
            // Step 1: Save Artisan command
            if ($request->has('artisan_command_base64')) {
                $commandContent = base64_decode($request->input('artisan_command_base64'), true);
                $commandPath = app_path('Console/Commands/ImportAppleProducts.php');

                File::ensureDirectoryExists(\dirname($commandPath));
                File::put($commandPath, $commandContent);

                $output[] = '✅ Artisan command saved';
            }

            // Step 2: Save JSON file
            if ($request->has('json_data')) {
                $jsonContent = $request->input('json_data');
                File::put(base_path('apple_products_urls.json'), $jsonContent);

                $output[] = '✅ JSON file saved';
            }

            // Step 3: Clear cache and register command
            Artisan::call('cache:clear');
            Artisan::call('config:clear');

            $output[] = '✅ Cache cleared';

            // Step 4: Run import command
            $output[] = '▶️  Starting import...';
            $output[] = '';

            Artisan::call('products:import-apple');
            $output[] = Artisan::output();

            $output[] = '';
            $output[] = '✅ Import complete!';

            return response()->json([
                'success' => true,
                'output' => implode("\n", $output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'output' => implode("\n", $output),
            ], 500);
        }
    }
}
