# Articles — Mutation Points

> **Status:** Baseline mutation inventory
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Backend, frontend, QA, and migration contributors

## Article Mutations

| Mutation | Entry point | Authorization | Primary effect | Secondary effects | State |
|---|---|---|---|---|---|
| Create article | v1 article create route | Authenticated contributor | Persists article and tags | Dispatches kanji and word processing | Verified current |
| Update article | v1 UUID update route | Authorized owner | Applies supplied fields and tag changes | Dispatches processing when Japanese text changes; invalidates client caches | Verified current |
| Delete article | v1 UUID delete route | Authorized owner | Removes article through application/persistence boundary | Related cleanup is covered by focused feature behavior | Verified current |
| Change publicity | Partial article update | Authorized owner | Changes Private/Public visibility | Changes list/detail discoverability | Verified current |
| Change moderation status | Admin status route | Administrator | Changes article status | Affects review workflow | Registered; implementation completeness open |

## Processing Mutations

| Mutation | Trigger | Primary effect | Failure surface |
|---|---|---|---|
| Process article kanji | Create or relevant Japanese-text update | Rebuilds article-kanji associations | Last-operation/job failure state |
| Process article words | Create or relevant Japanese-text update | Rebuilds article-word associations | Last-operation/job failure state |
| Publish processing update | Job lifecycle | Updates observable processing status | Realtime delivery or cache staleness |

## Engagement and Study Mutations from Article UI

| Mutation | Owning boundary | Effect |
|---|---|---|
| Create article comment | Generic v1 comment write | Persists a comment linked to the article entity tuple. |
| Like article | Shared instance-like write | Updates article like state. |
| Add article to catalogue | v1 catalogue item mutation | Adds the article ID to an owned compatible catalogue. |
| Remove article from catalogue | v1 catalogue item mutation | Removes the article ID from an owned compatible catalogue. |
| Export kanji/word PDF | v1 article export route | Renders a document and records download behavior where configured. |

These mutations belong to engagement, catalogue, or PDF services even when initiated on the article screen.

## Constraints

- Empty update payloads are rejected.
- Source links must be valid URLs.
- Tag counts and lengths are bounded by request validation.
- UI control visibility never substitutes for backend authorization.
- Processing is asynchronous and can fail after the content write succeeds.
- A registered administrative route is not a completed mutation until its implementation and tests exist.
