<?php

namespace Forestry\LogCabin\Laravel\Tests;

use Forestry\LogCabin\Laravel\Jobs\PushLogEntriesJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class LogCabinHandlerTest extends TestCase
{
    public function test_it_does_not_dispatch_when_disabled(): void
    {
        config(['logcabin.enabled' => false]);
        Bus::fake();

        Log::channel('logcabin')->error('something broke');

        // enabled=false must stop the dispatch, not just delivery: dispatching
        // is itself a write to the queue.
        Bus::assertNotDispatched(PushLogEntriesJob::class);
    }

    public function test_it_dispatches_when_enabled(): void
    {
        config(['logcabin.enabled' => true]);
        Bus::fake();

        Log::channel('logcabin')->error('something broke');

        Bus::assertDispatched(PushLogEntriesJob::class);
    }

    public function test_a_failing_queue_backend_does_not_break_the_caller(): void
    {
        // Reproduce the migration case: the database queue driver with no
        // `jobs` table, so dispatching throws. The handler must swallow it.
        config([
            'logcabin.enabled' => true,
            'queue.default' => 'database',
            'database.default' => 'logcabin_testing',
            'database.connections.logcabin_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        // No exception should escape: the log call returns normally even
        // though the `insert into jobs` fails.
        Log::channel('logcabin')->error('logged mid-migration');

        $this->assertTrue(true);
    }
}
