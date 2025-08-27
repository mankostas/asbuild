<?php

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Resolve the public path relative to the backend directory. This allows the
// backend to operate as a standalone API without relying on a public folder at
// the project root.
$publicPath = __DIR__ . '/public';

if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

require_once $publicPath . '/index.php';
