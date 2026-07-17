<?php

/*
 * Log Cabin — self-hosted log, heartbeat and uptime monitoring for web apps.
 * Copyright (C) 2026 Forestry Labs
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
