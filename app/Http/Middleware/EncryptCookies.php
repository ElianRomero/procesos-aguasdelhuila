<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * Cookies que NO se encriptan (para que JS pueda leer XSRF-TOKEN si lo necesitas).
     */
    protected $except = [
        'XSRF-TOKEN',
    ];
}
