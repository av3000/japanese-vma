<?php

use App\Application\Processing\Authorization\ArticleProcessingChannelAuthorizer;
use App\Application\Processing\Events\ProcessingStatusUpdated;
use App\Infrastructure\Auth\Broadcasting\ArticleProcessingChannel;
use App\Infrastructure\Auth\Broadcasting\UserChannel;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Channel authorisation. Requests reach here through the `auth:api` middleware registered in
| BroadcastServiceProvider, so an anonymous request is a 401 before any channel class runs;
| a class returning false is a 403.
|
*/

// Article processing status: exactly the users who may view the article (issue #252).
// The principal is read from the `api` guard; the default `web` guard is never populated here.
Broadcast::channel(ArticleProcessingChannelAuthorizer::CHANNEL, ArticleProcessingChannel::class, ['guards' => ['api']]);

// A user's own channel, carrying the processing status of every article they own (#263).
Broadcast::channel(ProcessingStatusUpdated::OWNER_CHANNEL_PREFIX.'{uuid}', UserChannel::class, ['guards' => ['api']]);
