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

];
