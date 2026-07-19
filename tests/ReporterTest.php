<?php

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
