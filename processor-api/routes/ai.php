<?php

use App\Mcp\Servers\StudyServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| MCP servers
|--------------------------------------------------------------------------
|
| laravel/mcp loads this file itself, outside the `web` and `api` route groups, so these routes
| carry neither CSRF protection nor a throttle until a server adds them. The study server is
| public and read-only; it is registered only where `study_mcp.enabled` is true.
|
*/

if (config('study_mcp.enabled')) {
    Mcp::web('/mcp/study', StudyServer::class);
}
