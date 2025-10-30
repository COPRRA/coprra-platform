<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

/** @var Application $app */
$request = Request::create('/test', 'GET');

$middleware = $app->make(SecurityHeaders::class);

$response = $middleware->handle($request, static function ($req) {
    return response('OK', 200);
});

echo 'Status: '.$response->getStatusCode()."\n";
foreach ($response->headers->all() as $name => $values) {
    echo $name.': '.implode(', ', $values)."\n";
}

echo 'CSP: ';
var_dump($response->headers->get('Content-Security-Policy'));
