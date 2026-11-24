<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Study MCP server
    |--------------------------------------------------------------------------
    |
    | Whether `/mcp/study` is registered. It is on everywhere except production,
    | so local clients and the MCP Inspector work without setup. Production stays
    | off until a maintainer sets STUDY_MCP_ENABLED=true (#716).
    |
    */

    'enabled' => env('STUDY_MCP_ENABLED', env('APP_ENV', 'production') !== 'production'),

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    |
    | Requests per minute that one client IP address may send to `/mcp/study`, through the `mcp`
    | limiter in RouteServiceProvider. Callers behind one address share one bucket, which includes
    | hosted assistants that call from their vendor's servers. A value that is not a whole number
    | of at least 1 falls back to 30, because a limit of 0 would answer 429 to every request.
    |
    */

    'rate_per_minute' => max(0, (int) env('STUDY_MCP_RATE_PER_MINUTE', 30)) ?: 30,

];
