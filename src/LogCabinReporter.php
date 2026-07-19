<?php

namespace Forestry\LogCabin\Laravel;

use Forestry\LogCabin\Laravel\Jobs\PushLogEntriesJob;
use Throwable;

class LogCabinReporter
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function report(string $type, string $message, array $context = [], string $level = 'event'): void
    {
        PushLogEntriesJob::dispatch([[
            'level' => $level,
            'type' => $type,
            'message' => $message,
            'context' => $context ?: null,
            'exception' => null,
            'environment' => config('app.env'),
            'occurred_at' => now()->toIso8601String(),
        ]]);
    }

    public function reportException(Throwable $exception, array $context = []): void
    {
        PushLogEntriesJob::dispatch([[
            'level' => 'error',
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'context' => $context ?: null,
            'exception' => [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'environment' => config('app.env'),
            'occurred_at' => now()->toIso8601String(),
        ]]);
    }
}
