<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $this->defaultMessage($status);

            return response()->json([
                'message' => $message,
            ], $status);
        }

        Log::error('Unhandled exception', ['exception' => $e]);

        return response()->json([
            'message' => 'An unexpected error occurred.',
        ], 500);
    }

    protected function defaultMessage(int $status): string
    {
        return match ($status) {
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Server Error',
            default => 'Error',
        };
    }
}
