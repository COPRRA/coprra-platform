<?php
require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$updated = App\Models\ScraperJob::where('status', 'queued')->update([
    'status' => 'failed',
    'error_message' => 'Job superseded during QA retest',
    'completed_at' => now(),
]);

echo "queued_updated={$updated}\n";
