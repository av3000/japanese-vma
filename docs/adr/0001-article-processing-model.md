# ADR 0001: Article processing model

- **Status:** Accepted (2026-09-04)
- **Deciders:** repository owner
- **Source:** [Async processing audit](../architecture/async-processing/README.md), findings F-04, F-06, F-11, F-14, F-21; extends [`docs/superpowers/specs/2026-06-05-article-kanji-word-processing-design.md`](../superpowers/specs/2026-06-05-article-kanji-word-processing-design.md)
- **Implements via:** issue specs P1-2, P1-3, P1-4, P1-5, P1-6, P4-1 in [`issue-specs.md`](../architecture/async-processing/issue-specs.md)

## Context

Article creation and Japanese title/content updates dispatch two queued jobs (`kanji_extraction`, `words_extraction`). Each attempt of each job inserts a new `last_operations` row. REST exposes only the kanji row as `processing_status`; the socket delivers both. Retries create fresh `pending` rows, timeouts leave rows in `processing`, list reads load the whole history and dedupe in memory, and the table is named for an append-only shape it no longer needs. Two jobs also give no parallelism benefit on the single production worker process.

## Decision

1. **One job, one task type.** `ProcessArticleContentJob` replaces both jobs. Task type is `article_content_processing`. It computes kanji attachments, word attachments, and JLPT level counters for the article in one run and one persistence transaction.
2. **One row per `(entity_type, entity_id, task_type)`, updated in place.** Table `processing_states`, model `ProcessingState`, unique on that triple. Columns: `status`, `attempt`, `max_attempts`, `content_version`, `started_at`, `finished_at`, `error_code`, `error_message` (sanitised), `metadata` (counts only), timestamps. No history table; Horizon `failed_jobs` and Sentry keep the failure record.
3. **Statuses:** `pending`, `processing`, `completed`, `failed`, `superseded`. `superseded` is written when a job's `content_version` no longer matches the article and is exposed to clients as a terminal state (badge hidden).
4. **Row lifecycle starts in the write transaction.** `createArticle` and a reprocessing `updateArticle` upsert the row as `pending` with the new `content_version` before dispatching (after commit). The create response body includes `processing_status`.
5. **Entity and task types are enums** (`ProcessingEntityType`, `ProcessingTaskType`), stored as their string values. No Eloquent morph map.
6. **Public API field stays `processing_status`** with the existing `ProcessingStatusResource` shape plus `entity_id`, `sequence`, `attempt` (added in P4-4). Socket alias stays `OperationStatusUpdated` until P4-1 versions it.

## Consequences

- REST and socket describe the same single operation; the frontend needs no type branching.
- Retries and timeouts can no longer leave orphaned or duplicate rows; a terminal state is guaranteed together with `failed()` hooks and the stale sweeper (P0-2).
- List reads become one row per article by construction.
- Migration: backfill `processing_states` from the latest `last_operations` row per `(processable_id, task_type)`, mapping legacy task types to `article_content_processing`; drop `last_operations` one release after `ProcessArticleContentJob` is live.
- Queued payloads for the old job classes must be drained or the old classes kept for one release (rollout note in P1-3).

## Alternatives considered

- **Keep two jobs and expose both statuses.** Rejected: doubles UI states for no user value; no parallelism gain at one worker process.
- **Append-only history with a `current` pointer.** Rejected for now: no reader needs history; adds a join to every list read. Revisit if an audit trail becomes a product requirement.
- **Map `superseded` to `completed`.** Rejected: hides that the shown attachments came from a different content version.
- **Keep the `last_operations` name.** Rejected: renaming during the table migration is cheaper than a second migration later.
