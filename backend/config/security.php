<?php

return [
    'cors' => [
        'allowed_origins' => ['*'],
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
