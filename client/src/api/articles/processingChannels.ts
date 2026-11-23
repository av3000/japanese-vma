/**
 * The socket vocabulary, in one place (#266).
 *
 * These three strings are a contract with the backend: the channel names come from
 * `ArticleProcessingChannelAuthorizer::CHANNEL` and `ProcessingStatusUpdated::OWNER_CHANNEL_PREFIX`,
 * the event name from `ProcessingStatusUpdated::broadcastAs()`. They were versioned together
 * with the ADR 0001 rename, so a tab loaded before that deploy hears nothing and falls back to
 * polling until it reloads.
 */
export const PROCESSING_STATUS_EVENT = '.ProcessingStatusUpdated';

export const articleProcessingChannel = (articleUuid: string) => `processing_states.${articleUuid}`;

export const ownerProcessingChannel = (userUuid: string) => `App.User.${userUuid}`;
