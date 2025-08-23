<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ETag
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->isMethodCacheable() && $response->getStatusCode() === 200) {
            $etag = md5($response->getContent());
            $response->setEtag($etag);

            if ($request->headers->get('If-None-Match') === $etag) {
                $response->setNotModified();
            }
        }

        return $response;
    }
}
