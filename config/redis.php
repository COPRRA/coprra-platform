<?php

declare(strict_types=1);

use Illuminate\Support\Str;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection
    |--------------------------------------------------------------------------
    |
    | This option controls the default connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'client' => env('REDIS_CLIENT', 'phpredis'),

    /*
    |--------------------------------------------------------------------------
    | Redis Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the redis connections setup for your application.
    | Of course, examples of configuring each available type of connection
    | is shown below. You are free to add more as needed.
    |
    */

    'connections' => [
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis Sentinel
    |--------------------------------------------------------------------------
    |
    | This configuration is for the sentinel servers when using Redis Sentinel
    | instances to manage your Redis servers. This will enforce connections
    | to your sentinel servers and allow you to set sentinel configuration.
    |
    */

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
];
