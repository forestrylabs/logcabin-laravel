<?php

namespace Forestry\LogCabin\Laravel\Logging;

use Forestry\LogCabin\Laravel\Jobs\PushLogEntriesJob;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

class LogCabinHandler extends AbstractProcessingHandler
{
    // When false, records are dropped instead of shipped. Disabled while a
    // batch is being delivered so logging during delivery can't loop back in.
    protected static bool $capturing = true;

    /**
     * Run $callback with capture disabled.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutCapturing(callable $callback): mixed
    {
        $previous = self::$capturing;
        self::$capturing = false;

        try {
            return $callback();
        } finally {
            self::$capturing = $previous;
        }
    }

    protected function write(LogRecord $record): void
    {
        if (! self::$capturing) {
            return;
        }

        // Also checked here, not only in the job: dispatching writes to the
        // queue, so a disabled package must not dispatch at all.
        if (! config('logcabin.enabled')) {
            return;
        }

        $exception = $record->context['exception'] ?? null;

        // The queue backend may be unavailable, e.g. the database driver
        // before the jobs table exists during a migration. Logging must not
        // break the code that emitted the log, so swallow dispatch failures.
        // Use error_log() rather than the logger to avoid recursing back here.
        try {
            PushLogEntriesJob::dispatch([[
                'level' => strtolower($record->level->name),
                'type' => $exception instanceof Throwable ? get_class($exception) : null,
                'message' => $record->message,
                'context' => $this->contextWithoutException($record->context),
                'exception' => $exception instanceof Throwable ? $this->normalizeException($exception) : null,
                'environment' => config('app.env'),
                'occurred_at' => now()->toIso8601String(),
            ]]);
        } catch (Throwable $e) {
            error_log('Log Cabin: could not dispatch log entries: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function contextWithoutException(array $context): array
    {
        unset($context['exception']);

        return $context;
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeException(Throwable $exception): array
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ];
    }
}
