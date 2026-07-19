<?php

namespace Forestry\LogCabin\Laravel;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;
use Forestry\LogCabin\Laravel\Console\SendHeartbeatCommand;

class LogCabinServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/logcabin.php', 'logcabin');

        $this->app->singleton(LogCabinReporter::class);

        if (config('logcabin.auto_attach_to_stack', true)) {
            $this->attachToStackChannel();
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/logcabin.php' => config_path('logcabin.php'),
        ], 'logcabin-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendHeartbeatCommand::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            $schedule->command(SendHeartbeatCommand::class)
                ->everyMinute()
                ->when(fn () => now()->minute % max((int) config('logcabin.heartbeat_interval', 5), 1) === 0);

            $schedule->call(fn () => Cache::put('logcabin_scheduler_heartbeat', now()))
                ->everyMinute();
        });
    }

    private function attachToStackChannel(): void
    {
        config([
            'logging.channels.logcabin' => [
                'driver' => 'monolog',
                'handler' => \Forestry\LogCabin\Laravel\Logging\LogCabinHandler::class,
                'level' => config('logcabin.log_level', 'error'),
            ],
        ]);

        $stackChannels = config('logging.channels.stack.channels', []);

        if (! in_array('logcabin', $stackChannels, true)) {
            config(['logging.channels.stack.channels' => [...$stackChannels, 'logcabin']]);
        }
    }
}
