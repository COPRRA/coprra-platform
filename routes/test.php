<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/test', static function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Server is working',
        'timestamp' => now()->toISOString(),
    ]);
});
