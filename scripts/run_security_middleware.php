<?php

declare(strict_types=1);

// Boot Laravel application
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->instance('env', 'local');

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** @var Container $app */
$request = Request::create('/test', 'GET');

$middleware = $app->make(SecurityHeaders::class);

$response = $middleware->handle($request, static function ($req) {
    return new Response('OK', 200);
});

echo 'Status: '.$response->getStatusCode().PHP_EOL;
echo 'CSP: '.var_export($response->headers->get('Content-Security-Policy'), true).PHP_EOL;
echo 'X-Frame-Options: '.var_export($response->headers->get('X-Frame-Options'), true).PHP_EOL;
echo 'Referrer-Policy: '.var_export($response->headers->get('Referrer-Policy'), true).PHP_EOL;
