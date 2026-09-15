<?php

return [
    'enabled' => env('GLOBAL_COMMAND_ENABLED', false),
    'metrics_ttl_seconds' => 60,
    'liquidity_version' => 'supply-v1',
    'diaspora_sync_enabled' => env('GLOBAL_COMMAND_DIASPORA_SYNC_ENABLED', false),
    'diaspora_sync_queue_connection' => env('GLOBAL_COMMAND_DIASPORA_QUEUE', 'database'),
    'diaspora_sync_cache_store' => env('GLOBAL_COMMAND_DIASPORA_CACHE', 'database'),
    'diaspora_sync_requests_per_minute' => 10,
];
