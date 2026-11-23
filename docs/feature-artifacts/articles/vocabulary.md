# Articles — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product, frontend, backend, and documentation contributors

| Term | Meaning |
|---|---|
| **Article** | User-authored Japanese reading content with titles, body content, source, visibility, author, and related data. |
| **Author** | User identity presented as the article creator. |
| **Publicity** | Visibility state: Private or Public. |
| **Article status** | Moderation/processing-facing enum: Pending, Processed, Under Review, Rejected, or Approved. |
| **Processing status** | Asynchronous processing state: pending, processing, completed, failed, or superseded. |
| **Attached kanji** | Kanji associated with an article by processing or persistence relationships. |
| **Attached word** | Word associated with an article by processing or persistence relationships. |
| **Include option** | Detail-query flag controlling optional enrichment such as words or kanji. |
| **Engagement** | Comments, likes, views, downloads, and hashtags related to the article. |
| **Owner** | User allowed by backend policy to change or delete the article. |
| **Generated client** | Orval-produced frontend transport derived from the v1 OpenAPI contract. |

## Processing Vocabulary

> **Settled by:** ADR 0001, issue #266 (2026-09-19). One concept had seven names; this table is
> the only one that applies now.

| Concept | Settled name | Retired names |
|---|---|---|
| Current state of one entity's processing task | `ProcessingState` | `LastOperation` |
| The table | `processing_states` | `last_operations` |
| Status enum | `App\Domain\Processing\Enums\ProcessingStatus` | `LastOperationStatus` |
| Snapshot passed around the app | `App\Domain\Processing\DTOs\ProcessingStateDTO` | `ArticleProcessingStateDTO` |
| Broadcast event class | `App\Application\Processing\Events\ProcessingStatusUpdated` | `AsyncLastOperationStatusUpdated` |
| Public API field | `processing_status` | unchanged, and deliberately so: it is already generated into the client |
| HTTP resource | `App\Http\v1\Processing\Resources\ProcessingStatusResource` | unchanged class, moved out of the `LastOperations` namespace |

### Socket contract

| | Value | Previous value |
|---|---|---|
| Article channel | `private-processing_states.{article uuid}` | `private-last_operations.{article uuid}` |
| Owner channel | `private-App.User.{owner uuid}` | unchanged |
| Event alias | `.ProcessingStatusUpdated` | `.OperationStatusUpdated` |

The channel and the alias were versioned together in one release. A browser tab loaded before
that deploy subscribes to the old channel, receives nothing, and falls back to polling until it
reloads; the fallback is the reason the rename was acceptable. The client holds all three
strings in `client/src/api/articles/processingChannels.ts`.

### `metadata` keys

`metadata` is a free-form object on the processing state, replaced (not merged) on each
transition, so it always describes the transition that wrote it.

| Key | Written by | Meaning |
|---|---|---|
| `kanji_count` | completion | Number of distinct kanji attached to the article by that run. |
| `word_count` | completion | Number of distinct dictionary words attached by that run. |
| `attempt` | failure recorded by the queue's `failed()` hook | Which attempt failed. |
| `exception` | failure with a `Throwable` | Exception class name; never the message, which is sanitised into `error_message`. |
| `reason` | the stale sweeper | Why a row was failed without the job reporting, currently only `no heartbeat`. |

Failure detail is not in `metadata`: `error_code` and `error_message` are columns, and the full
exception goes to the logs and Sentry.

## Avoided Ambiguities

- Do not use **published** as a synonym for both public visibility and Approved moderation status; these are distinct concepts.
- Do not use **processed** to mean that every asynchronous operation succeeded unless the processing-status evidence confirms completion.
- Use **UUID** for the public v1 identity where the route expects it, and **numeric ID** only where the current contract explicitly does so.
- Use **hashtags** for the normalized domain concept even where a compatibility payload still accepts `tags`.

## Sources

- `processor-api/app/Domain/Shared/Enums/ArticleStatus.php`
- `processor-api/app/Domain/Shared/Enums/PublicityStatus.php`
- `processor-api/app/Domain/Processing/Enums/ProcessingStatus.php`
- `processor-api/app/Domain/Articles/`
