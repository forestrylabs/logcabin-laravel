<?php

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

class PushHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function __construct(public array $metrics)
    {
        $this->onQueue(config('logcabin.queue', 'default'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(ApiClient $client): void
    {
        if (! config('logcabin.enabled')) {
            return;
        }

        // See PushLogEntriesJob: failures are handled here so they don't bubble
        // to the worker and get logged back through logcabin.
        try {
            LogCabinHandler::withoutCapturing(fn () => $client->sendHeartbeat($this->metrics));
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
        Log::channel('single')->warning('Log Cabin: failed to push heartbeat after retries.', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
