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
