<?php
require __DIR__.'/../vendor/autoload.php';
$env = getenv('APP_ENV') ?: 'production';
$dsn = getenv('SENTRY_DSN') ?: ($_ENV['SENTRY_DSN'] ?? '');
if ('production' === $env || '' === $dsn) {
    http_response_code(403);
    exit('Forbidden');
}

try {
    Sentry\init(['dsn' => $dsn, 'traces_sample_rate' => 1.0]);
    
    echo "Sentry SDK Initialized.<br>";
    
    $eventId = Sentry\captureMessage('Direct Transport Test from standalone script.');
    
    if ($eventId) {
        echo "Event captured and sent with ID: " . $eventId . "<br>";
        echo "Flushing transport queue...<br>";
        if (function_exists('Sentry\flush')) {
            Sentry\flush();
        } else {
            // Try alternative flush method
            $client = Sentry\ClientBuilder::create(['dsn' => $dsn])->getClient();
            if ($client && method_exists($client, 'flush')) {
                $client->flush();
            }
        }
        echo "Flush complete. Check Sentry.io dashboard now.";
    } else {
        echo "Failed to capture event. Something is wrong with the SDK initialization.";
    }
    
} catch (\Throwable $e) {
    echo "An exception occurred during the test:<br><pre>";
    var_dump($e);
    echo "</pre>";
}
