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

namespace Forestry\LogCabin\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Forestry\LogCabin\Laravel\Http\ApiClient;
use Forestry\LogCabin\Laravel\Logging\LogCabinHandler;
use Throwable;

class PushLogEntriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function __construct(public array $entries)
    {
        $this->onQueue(config('logcabin.queue', 'default'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 300, 900];
    }

    public function handle(ApiClient $client): void
    {
        if (! config('logcabin.enabled')) {
            return;
        }

        // Handle failures here rather than letting them bubble: an exception
        // out of the job is reported by the queue worker via the default log
        // channel, which may route back through logcabin and loop. Disabling
        // capture during the push guards against the same loop.
        try {
            LogCabinHandler::withoutCapturing(fn () => $client->sendLogs($this->entries));
        } catch (RequestException $exception) {
            if ($exception->response->status() === 429) {
                // Rate limited; wait and retry.
                $this->release($this->retryAfter($exception));

                return;
            }

            $this->giveUpOrRetry($exception);
        } catch (Throwable $exception) {
            // Connection errors, timeouts, etc.
            $this->giveUpOrRetry($exception);
        }
    }

    /**
     * Delay before the next attempt, using the server's Retry-After header
     * when available.
     */
    protected function retryAfter(RequestException $exception): int
    {
        $header = $exception->response->header('Retry-After');

        return is_numeric($header) && $header !== '' ? (int) $header : 60;
    }

    /**
     * Release for another attempt, or fail once attempts are exhausted.
     * Failing instead of throwing keeps the error off the default log channel.
     */
    protected function giveUpOrRetry(Throwable $exception): void
    {
        if ($this->attempts() >= $this->tries) {
            $this->fail($exception);

            return;
        }

        $this->release($this->backoff()[$this->attempts() - 1] ?? 60);
    }

    public function failed(Throwable $exception): void
    {
        // Deliberately logs to the "single" channel rather than the default
        // (possibly "stack") channel, since "stack" may include the
        // logcabin channel itself — logging a delivery failure through it
        // would attempt another delivery and could loop.
        Log::channel('single')->warning('Log Cabin: failed to push log entries after retries.', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
