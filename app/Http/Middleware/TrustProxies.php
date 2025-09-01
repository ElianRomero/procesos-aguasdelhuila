<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class TrustProxies extends Middleware
{
    /**
     * A qué proxies confiar.
     * - '*' confía en todos (útil si estás detrás de Cloudflare/Proxy y no quieres listar IPs)
     * - o bien un array con IPs/hosts específicos.
     */
    protected $proxies = '*';

    /**
     * Qué cabeceras usar para detectar el cliente real y el esquema https.
     */
    protected $headers = SymfonyRequest::HEADER_X_FORWARDED_FOR
        | SymfonyRequest::HEADER_X_FORWARDED_HOST
        | SymfonyRequest::HEADER_X_FORWARDED_PORT
        | SymfonyRequest::HEADER_X_FORWARDED_PROTO
        | SymfonyRequest::HEADER_X_FORWARDED_PREFIX;
}
