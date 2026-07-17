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

return [
    'enabled' => env('LOGCABIN_ENABLED', true),

    'endpoint' => env('LOGCABIN_ENDPOINT', 'https://logcabin.example.com'),

    'token' => env('LOGCABIN_TOKEN'),

    'queue' => env('LOGCABIN_QUEUE', 'default'),

    'log_level' => env('LOGCABIN_LOG_LEVEL', 'error'),

    // Automatically append the `logcabin` log channel to the `stack`
    // channel's list so existing Log::error()/exceptions ship with zero
    // code changes. Disable if your app doesn't log through `stack`.
    'auto_attach_to_stack' => env('LOGCABIN_AUTO_ATTACH', true),

    // Minutes between automatic heartbeat pings.
    'heartbeat_interval' => env('LOGCABIN_HEARTBEAT_INTERVAL', 5),
];
