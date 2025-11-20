<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Allow clients (and curl) to read the CSRF token from the cookie
        // so it can be echoed back in the X-XSRF-TOKEN header.
        'XSRF-TOKEN',
    ];
}
