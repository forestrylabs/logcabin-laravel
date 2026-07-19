<?php

namespace Forestry\LogCabin\Laravel\Tests;

use Forestry\LogCabin\Laravel\Jobs\PushHeartbeatJob;
use Illuminate\Support\Facades\Bus;

class ServiceProviderTest extends TestCase
{
    public function test_it_attaches_the_logcabin_channel_to_the_stack(): void
    {
        $this->assertContains('logcabin', config('logging.channels.stack.channels'));
    }

    public function test_heartbeat_command_dispatches_a_heartbeat_job(): void
    {
        Bus::fake();

        $this->artisan('logcabin:heartbeat')->assertSuccessful();

        Bus::assertDispatched(PushHeartbeatJob::class, function (PushHeartbeatJob $job) {
            return $job->metrics['status'] === 'healthy'
                && $job->metrics['php_version'] === PHP_VERSION;
        });
    }

    public function test_disabling_logcabin_stops_the_heartbeat_job(): void
    {
        Bus::fake();

        config(['logcabin.enabled' => false]);

        $this->artisan('logcabin:heartbeat')->assertSuccessful();

        Bus::assertNotDispatched(PushHeartbeatJob::class);
    }
}
