<?php

namespace Forestry\LogCabin\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Forestry\LogCabin\Laravel\Jobs\PushHeartbeatJob;

class SendHeartbeatCommand extends Command
{
    protected $signature = 'logcabin:heartbeat';

    protected $description = 'Send a health heartbeat to the Log Cabin panel';

    public function handle(): int
    {
        if (! config('logcabin.enabled')) {
            return self::SUCCESS;
        }

        PushHeartbeatJob::dispatch($this->gatherMetrics());

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    protected function gatherMetrics(): array
    {
        $diskFree = @disk_free_space('/');
        $diskTotal = @disk_total_space('/');

        return [
            'status' => 'healthy',
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'queue_pending_count' => $this->queueCount('jobs'),
            'queue_failed_count' => $this->queueCount('failed_jobs'),
            'disk_free_bytes' => $diskFree !== false ? (int) $diskFree : null,
            'disk_total_bytes' => $diskTotal !== false ? (int) $diskTotal : null,
            'scheduler_last_run_at' => Cache::get('logcabin_scheduler_heartbeat'),
        ];
    }

    protected function queueCount(string $table): ?int
    {
        // Only meaningful when the consuming app uses the database queue
        // driver; other drivers (redis, sqs, etc.) aren't introspected here.
        if (config('queue.default') !== 'database') {
            return null;
        }

        try {
            return DB::table($table)->count();
        } catch (\Throwable) {
            return null;
        }
    }
}
