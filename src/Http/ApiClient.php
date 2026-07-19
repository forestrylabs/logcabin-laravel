<?php

namespace Forestry\LogCabin\Laravel\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClient
{
    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function sendLogs(array $entries): Response
    {
        return $this->request()->post('/api/v1/logs', ['logs' => $entries])->throw();
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function sendHeartbeat(array $metrics): Response
    {
        return $this->request()->post('/api/v1/heartbeat', $metrics)->throw();
    }

    protected function request()
    {
        return Http::baseUrl(rtrim(config('logcabin.endpoint'), '/'))
            ->withToken(config('logcabin.token'))
            ->acceptJson();
    }
}
