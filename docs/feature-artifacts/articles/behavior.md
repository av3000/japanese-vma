# Articles — Behavior

> **Status:** Baseline; verified behavior and incomplete administration separated
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
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

Create dispatches kanji and word processing jobs. Updates dispatch processing only when relevant Japanese title/content input changes. The initial write response is therefore distinct from completed extraction.

Processing state uses these values:

```text
pending -> processing -> completed
                      -> failed
```

The frontend subscription/query boundary can update cached article state when processing events arrive.

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
