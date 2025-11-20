<?php

declare(strict_types=1);

/**
 * Add this to routes/api.php.
 */

use App\Http\Controllers\Api\BatchImportController;

// Batch Import Endpoint - For automated product imports
Route::post('/products/batch-import', [BatchImportController::class, 'batchImport'])
    ->name('products.batch-import')
;
