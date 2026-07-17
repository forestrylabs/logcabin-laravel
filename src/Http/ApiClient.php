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
