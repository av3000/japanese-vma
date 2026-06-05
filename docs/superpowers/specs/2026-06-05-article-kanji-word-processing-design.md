# Article Kanji Word Processing Design

**Goal**

Replace separate article kanji and word processing jobs with one article content-processing job that drives a single frontend-facing processing status over the existing last-operation socket channel.

**Scope**

- Replace article create/update dispatch of `ProcessArticleKanjisJob` and `ProcessArticleWordsJob` with one `ProcessArticleContentJob`.
- Use one task type: `article_kanji_word_processing`.
- Keep the frontend-facing API field as a single `processing_status`.
- Keep the existing socket channel and event name:
  - channel: `last_operations.{articleUuid}`
  - event: `.OperationStatusUpdated`
- Keep the existing `ProcessingStatusResource` payload shape.
- Make article content processing content-replacing and idempotent for both kanji and word attachments.

**Non-goals**

- No new frontend multi-status UI.
- No `processing_statuses` API field.
- No operation selector that merges independent kanji and word statuses.
- No new queue infrastructure, Horizon configuration, or deployment topology change.
- No article detail/list redesign.

## Current State

`ArticleService` currently dispatches two jobs:

- `ProcessArticleKanjisJob`
  - input: article UUID and Japanese content
  - task type: `kanji_extraction`
  - extracts kanji characters from content
  - syncs article kanji attachments
- `ProcessArticleWordsJob`
  - input: article UUID and title-plus-content text
  - task type: `words_extraction`
  - extracts word IDs from title plus content
  - syncs article word attachments

Both jobs call `LastOperationService::startOperation()` and `LastOperationService::updateStatus()`. Every status update broadcasts `OperationStatusUpdated` on `private-last_operations.{articleUuid}` through `AsyncLastOperationStatusUpdated`.

The frontend already listens with `useArticleSubscription(articleUuid)`. That hook merges the received `ProcessingStatusResource` into one `processing_status` field in both:

- article detail cache: `['article', articleUuid]`
- article list cache: `['articles']`

The current backend article detail/list payloads read only latest `kanji_extraction` state:

- `ArticleService::getArticle()` calls `getLatestState(..., 'kanji_extraction')`
- `ArticleService::getArticlesList()` calls `getBatchLatestStates(..., 'kanji_extraction')`

This means word processing is recorded and broadcast independently, but the API and frontend shape still behave as if there is only one kanji processing state.

## Chosen Approach

Use one article-level background operation for derived learning material.

Create `ProcessArticleContentJob` and make it the only job dispatched by article create/update for this workflow. It will call the existing kanji and word extraction/attachment services internally, but it will own one last-operation record with task type `article_kanji_word_processing`.

This removes the concurrency/status-selection problem from the frontend. The frontend does not need to know whether kanjis, words, or both are currently being processed. It only needs to know whether article-derived learning material is processing, completed, or failed.

## Backend Design

### `ProcessArticleContentJob`

The new job should:

1. Start one last operation:
   - entity ID: article UUID
   - entity type: `article`
   - task type: `article_kanji_word_processing`
2. Mark the operation `processing`.
3. Extract unique kanjis from `content_jp`.
4. Attach/sync kanjis to the article.
5. Extract word IDs from `title_jp . content_jp`.
6. Attach/sync words to the article.
7. Mark the operation `completed` with metadata:

```php
[
    'kanji_count' => $kanjiCount,
    'word_count' => $wordCount,
    'message' => "Attached {$kanjiCount} kanjis and {$wordCount} words.",
]
```

If either kanji or word processing fails, the job should:

1. Mark the operation `failed`.
2. Include metadata naming the failed stage:

```php
[
    'stage' => 'kanji_attachment',
    'error' => $errorDescription,
]
```

or:

```php
[
    'stage' => 'word_attachment',
    'error' => $errorDescription,
]
```

3. Throw the exception so Laravel retry behavior remains active.

### Idempotency

The job should be content-replacing:

- latest content with kanjis replaces old article kanji attachments
- latest content with no kanjis clears old article kanji attachments
- latest title/content word extraction replaces old article word attachments
- latest title/content with no words clears old article word attachments

`WordAttachmentService` already syncs an empty word list. `KanjiAttachmentService` currently returns early when no kanjis are extracted, so it does not clear old kanji attachments. That should be fixed so empty kanji extraction syncs an empty kanji list for the article.

### ArticleService Dispatch Rules

Create:

- always dispatch `ProcessArticleContentJob` after article creation

Update:

- dispatch `ProcessArticleContentJob` when `content_jp` changes
- dispatch `ProcessArticleContentJob` when `title_jp` changes
- do not dispatch when neither `content_jp` nor `title_jp` changes

The job input should include:

- article UUID
- Japanese content text for kanji extraction
- combined title/content text for word extraction

## API Design

Keep the existing single field:

```json
"processing_status": {
  "id": 123,
  "type": "article_kanji_word_processing",
  "status": "processing",
  "metadata": {},
  "created_at": "2026-06-05T12:00:00+00:00",
  "updated_at": "2026-06-05T12:00:01+00:00"
}
```

Update article read paths to use the new task type:

- detail: latest `article_kanji_word_processing`
- list: batch latest `article_kanji_word_processing`

Do not expose separate kanji and word statuses to the frontend in this slice.

## Frontend Design

The frontend should keep using `useArticleSubscription(articleUuid)`.

The hook can continue to:

- listen to `last_operations.{articleUuid}`
- handle `.OperationStatusUpdated`
- merge payload into `processing_status`
- update article detail cache
- update article list cache
- invalidate article detail on `completed` or `failed`

Article detail and article list should continue to show one processing status. The frontend should not introduce special branching for kanji vs word processing.

Optional copy refinement is allowed if it remains small. For example, `ProcessingStatusAlert` can keep generic copy, or it can say that article learning material is processing. The implementation plan should not make copy refinement a blocker for the backend/socket contract.

## Cleanup

If no remaining callers exist after `ArticleService` switches to `ProcessArticleContentJob`, remove:

- `processor-api/app/Application/Articles/Jobs/ProcessArticleKanjisJob.php`
- `processor-api/app/Application/Articles/Jobs/ProcessArticleWordsJob.php`

Tests should stop asserting the old job classes and old task types for article create/update processing.

## Testing Strategy

Backend tests should cover:

1. Article create dispatches `ProcessArticleContentJob`.
2. Article update dispatches `ProcessArticleContentJob` when content changes.
3. Article update dispatches `ProcessArticleContentJob` when only title changes.
4. Article update does not dispatch when neither title nor content changes.
5. Job success extracts/syncs kanjis and words and writes completed metadata with both counts.
6. Job failure during kanji processing writes failed metadata with `stage`.
7. Job failure during word processing writes failed metadata with `stage`.
8. Empty kanji extraction clears old article kanji attachments.
9. Article detail returns `processing_status.type = article_kanji_word_processing`.
10. Article index returns `items.*.processing_status.type = article_kanji_word_processing`.

Frontend tests should cover:

1. `useArticleSubscription` merges an `article_kanji_word_processing` payload into detail cache.
2. `useArticleSubscription` merges an `article_kanji_word_processing` payload into article list cache.
3. Article list continues subscribing to articles whose `processing_status.status` is not `completed`.

Verification commands should use the repo-standard lanes:

- backend formatting: `vendor/bin/pint --dirty`
- backend tests from `processor-api/`:
  - `docker compose up -d --build db-test test-runner`
  - `docker compose exec test-runner composer test -- tests/Feature/Articles/StoreArticleTest.php`
  - `docker compose exec test-runner composer test -- tests/Feature/Articles/UpdateArticleTest.php`
  - `docker compose exec test-runner composer test -- tests/Feature/Articles/ProcessArticleContentJobTest.php`
  - `docker compose exec test-runner composer test -- tests/Feature/Articles/ShowArticleTest.php`
  - `docker compose exec test-runner composer test -- tests/Feature/Articles/IndexArticleTest.php`
- frontend tests from `client/`:
  - `npm run test -- src/api/articles/hooks/useArticleSubscription.test.ts`
  - rerun outside the sandbox if Vitest fails at startup with `spawn EPERM`

## Rollout Notes

This change affects the live queue worker path. Production queue workers run on the GCP VM through Docker Compose, while the backend web service runs on Render. The implementation summary should call out that the backend image/worker deploy must include the new job class and remove references to old job classes.

No queue backend, channel authorization, Redis, or deployment configuration change is expected.
