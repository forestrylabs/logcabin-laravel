<?php

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
