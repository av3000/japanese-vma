# Articles — Behavior

> **Status:** Baseline; verified behavior and incomplete administration separated
> **Last reviewed:** 2026-09-20
> **Evidence baseline:** Repository working tree inspected on 2026-09-20; processing behaviour re-read against the epic #241 remediation
> **Audience:** Frontend, backend, QA, and product-minded contributors

## Public Reads

- The v1 article index is public and returns a paginated list shaped for discovery.
- The v1 detail route is public but visibility policy determines whether the requested article is readable by the viewer.
- Detail responses can include related words and kanji, engagement information, hashtags, author data, and processing state.
- Include flags allow callers to suppress optional enrichment where supported.

## Authenticated Writes

Article create requires Japanese and English titles, Japanese content, a valid source URL, publicity, and authenticated identity. English content and tags are optional within request limits.

Article update is partial. At least one recognized update field must be present. Ownership/authorization is enforced by backend policy/application code rather than by whether the UI renders an edit control.

Delete is an authenticated, authorized operation and removes the article through the v1 application boundary.

## Processing Behavior

Create dispatches **one** consolidated operation, `article_content_processing`, which extracts
kanji and words together. Updates dispatch it only when Japanese title or content actually
changed. The job is dispatched after the database transaction commits, so it can never run
against a row that was rolled back. The initial write response is therefore distinct from
completed extraction.

State is one current row per `(entity_type, entity_id, task_type)` in `processing_states`,
updated in place rather than appended:

```text
pending -> processing -> completed
                      -> failed
                      -> superseded
```

`superseded` is terminal and means the article content changed while this run was in flight, so
its result was discarded and a newer run owns the row. Nothing is shown to the reader for a
superseded run; the newer run's status replaces it.

Terminal states carry their own detail: `completed` writes `kanji_count` and `word_count` into
`metadata`, `failed` writes `error_code` and a sanitised single-line `error_message`. Metadata is
replaced on each transition, never merged, so a failure from an earlier attempt cannot survive
into a later success.

A row that stops reporting — worker timeout, OOM, restart — is failed by the
`article-processing:sweep-stale` command, scheduled every five minutes, once it has gone 330
seconds without an update. That threshold is the job timeout plus the queue `retry_after` plus
margin, so a row is only swept when no attempt could still write to it.

### How the browser finds out

Two paths, in this order:

1. **Socket.** Each transition broadcasts `.ProcessingStatusUpdated` on
   `private-processing_states.{article uuid}`, and on `private-App.User.{owner uuid}` when the
   owner is known, so an owner's dashboard needs one subscription rather than one per article.
   The payload carries `entity_id`, `status`, `sequence`, `attempt`, `max_attempts` and
   `metadata`, and is the same shape as the REST `processing_status` field by construction.
2. **Polling fallback.** When the socket is not connected, the article detail and list queries
   poll every 5 s, dropping to 15 s after a minute of waiting. Polling is the correctness
   baseline: a transport outage degrades the experience without breaking it.

`sequence` increases on every transition and never resets, so a replayed or out-of-order event
is dropped rather than overwriting fresher state.

On a terminal event the client refetches the article detail and the first page of the attached
kanji and word lists, because processing changes the article itself (attachments, JLPT
counters) and not just the status row.

### Attached kanji and words

The detail response does not embed the attached lists. `include_kanjis` and `include_words`
default to false; a caller that wants them inline asks. The detail page reads them from the
ordinary kanji and word indexes filtered by `article_uuid`, a page at a time, so an article with
hundreds of matches no longer makes every detail read carry all of them.

## Visibility and Moderation

Publicity is a two-state visibility decision: Private or Public. Article status is a separate moderation-oriented enum with Pending, Processed, Under Review, Rejected, and Approved values.

The repository does not provide enough completed v1 administrative evidence to define a new authoritative transition diagram. Registered pending/status routes must be verified against controller implementations and focused tests before caller migration.

## Failure Behavior

Expected v1 failure categories include:

- validation failure for malformed or empty updates;
- unauthenticated write attempt;
- forbidden update/delete by a non-owner or unauthorized user;
- article not found;
- private article access denial;
- processing failure after the initial write;
- PDF export failure or unsupported output.

Transport failures should remain typed at the v1 boundary. Background failures must remain observable through processing state rather than retroactively changing a successful create response.

## Frontend Cache Behavior

- Article list queries use stable feature query keys and paginated React Query state.
- Detail writes invalidate or update the article detail and relevant article-list state.
- Realtime processing updates target article-related caches.
- Catalogue membership actions invalidate or optimistically update the owning catalogue-for-item state, not unrelated global state.

## Evidence

- `processor-api/tests/Feature/Articles/`
- `client/src/api/articles/`
- `client/src/routes/ArticleDetails/`
- `processor-api/routes/api_v1.php`
