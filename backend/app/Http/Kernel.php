<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [
        \App\Http\Middleware\SecurityHeaders::class,
        \App\Http\Middleware\RequestId::class,
        \App\Http\Middleware\ETag::class,
        \App\Http\Middleware\LogRequests::class,
    ];

    protected $middlewareAliases = [
        'tenant' => \App\Http\Middleware\ResolveTenant::class,
        'signed.url' => \App\Http\Middleware\SignedUrl::class,
    ];
}
