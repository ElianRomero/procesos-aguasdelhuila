<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Importante: son rutas relativas (sin dominio). Deben coincidir
     * con tus rutas definidas en routes/web.php o routes/api.php.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Tu webhook de Wompi (POST /webhook/wompi)
        'webhook/wompi',

        // Opcional: si en algún momento usas variantes
        // 'webhook/wompi/*',
        // 'api/webhook/wompi',
    ];
}
