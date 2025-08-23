<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $cors = config('security.cors');
        $response->headers->set('Access-Control-Allow-Origin', implode(',', $cors['allowed_origins'] ?? ['*']));
        $response->headers->set('Access-Control-Allow-Methods', implode(',', $cors['allowed_methods'] ?? ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']));
        $response->headers->set('Access-Control-Allow-Headers', implode(',', $cors['allowed_headers'] ?? ['Content-Type', 'Authorization', 'X-Requested-With']));

        $response->headers->set('Content-Security-Policy', config('security.csp'));
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
