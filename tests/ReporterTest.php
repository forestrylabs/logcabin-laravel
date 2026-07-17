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

use Forestry\LogCabin\Laravel\Facades\LogCabin;
use Forestry\LogCabin\Laravel\Jobs\PushLogEntriesJob;
use Illuminate\Support\Facades\Bus;
use RuntimeException;

class ReporterTest extends TestCase
{
    public function test_report_dispatches_a_log_job_with_the_given_payload(): void
    {
        Bus::fake();

        LogCabin::report('payment.failed', 'Charge declined', ['order_id' => 42]);

        Bus::assertDispatched(PushLogEntriesJob::class, function (PushLogEntriesJob $job) {
            $entry = $job->entries[0];

            return $entry['type'] === 'payment.failed'
                && $entry['message'] === 'Charge declined'
                && $entry['context'] === ['order_id' => 42]
                && $entry['level'] === 'event'
                && $entry['exception'] === null;
        });
    }

    public function test_report_exception_normalizes_the_throwable(): void
    {
        Bus::fake();

        LogCabin::reportException(new RuntimeException('boom'));

        Bus::assertDispatched(PushLogEntriesJob::class, function (PushLogEntriesJob $job) {
            $entry = $job->entries[0];

            return $entry['level'] === 'error'
                && $entry['type'] === RuntimeException::class
                && $entry['message'] === 'boom'
                && $entry['exception']['class'] === RuntimeException::class
                && $entry['exception']['message'] === 'boom';
        });
    }
}
