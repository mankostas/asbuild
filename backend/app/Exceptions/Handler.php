<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        if (env('SENTRY_DSN') && function_exists('Sentry\init')) {
            \Sentry\init(['dsn' => env('SENTRY_DSN')]);
            \Sentry\captureException($e);
        }
        parent::report($e);
    }

    public function render($request, Throwable $e)
    {
        Log::error('Unhandled exception', ['exception' => $e->getMessage()]);
        return response()->json([
            'message' => 'An unexpected error occurred.',
        ], 500);
    }
}
