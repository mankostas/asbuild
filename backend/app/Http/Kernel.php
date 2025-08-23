<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middleware = [];

    protected $middlewareAliases = [
        'tenant' => \App\Http\Middleware\ResolveTenant::class,
    ];
}
