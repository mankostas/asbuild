<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $cors = config('security.cors');

        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        // Filter out any empty strings to avoid sending an empty header value and
        // normalise origins to ignore trailing slashes.
        $allowedOrigins = array_map(
            static fn (string $o): string => rtrim($o, '/'),
            array_filter($cors['allowed_origins'] ?? [])
        );
        $origin = rtrim((string) $request->headers->get('Origin'), '/');

        if ($origin !== '') {
            if (empty($allowedOrigins) || in_array($origin, $allowedOrigins, true)) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
            }
        } elseif (!empty($allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigins[0]);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }
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
