<?php

return [
    'cors' => [
        // In production only allow requests from the configured frontend URL.
        // During local development explicitly allow the local frontend to
        // ensure credentialed requests work in browsers.
        'allowed_origins' => env('APP_ENV') === 'production'
            ? [env('FRONTEND_URL', '')]
            : [env('FRONTEND_URL', 'http://localhost:5173')],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
    ],
    'csp' => "default-src 'self'; frame-ancestors 'none'; object-src 'none';",
    'max_upload_size' => 5120, // 5 MB
    'allowed_upload_mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    'password' => [
        'min_length' => 12,
    ],
];
