<?php

return [
    'cors' => [
        // Allow only the configured frontend origin(s). Multiple origins can be
        // specified by separating them with commas in the FRONTEND_URL
        // environment variable. Any trailing slashes are removed to avoid
        // mismatches when comparing with the request origin.
        'allowed_origins' => array_map(
            static fn (string $origin): string => rtrim($origin, '/'),
            array_filter(
                array_map(
                    'trim',
                    explode(',', env('FRONTEND_URL', 'http://localhost:5173,http://127.0.0.1:5173'))
                )
            )
        ),
        'allowed_methods' => ['GET', 'POST', 'PATCH', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Tenant-Id'],
    ],
    'csp' => "default-src 'self'; frame-ancestors 'none'; object-src 'none';",
    'max_upload_size' => 5120, // 5 MB
    'allowed_upload_mimes' => ['jpg', 'jpeg', 'png', 'pdf'],
    'password' => [
        'min_length' => 12,
    ],
];
