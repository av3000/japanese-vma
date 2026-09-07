# Community Post v1 Public Read Specification

> **Status:** Approved design; implementation is blocked by issue #126
> **Issue:** [#135 — POST-READ-BE-01](https://github.com/av3000/japanese-vma/issues/135)
> **Last reviewed:** 2026-09-01
> **Target branch:** `feature/issue-135-implement-public-v1-community-post-reads`
> **Audience:** Backend implementers, frontend contract consumers, and reviewers

## 1. Outcome

Add public v1 Community Post list and detail reads with:

- one typed `GET /api/v1/posts` list/search endpoint;
- one `GET /api/v1/posts/{identifier}` detail endpoint whose canonical identity is UUID;
- typed keyword, hashtag, topic, sort, and pagination filters;
- deterministic newest and popularity ordering;
- batched author, hashtag, and engagement enrichment;
- explicit locked state without hiding locked posts;
- authenticated-only, best-effort detail-view recording; and
- OpenAPI shapes that generate usable Orval clients without handwritten frontend wire types.

The v1 contract is additive. Legacy Post reads remain available until the frontend and dependent Comment/Like slices have migrated and been deployed.

## 2. Dependencies and Delivery Gate

| Dependency | Current status | Effect on #135 |
| --- | --- | --- |
| #126 `FOUND-DB-01` | Open on 2026-09-01 | Blocks application-code implementation and any claim that DB-backed verification is complete. |
| #127 `FOUND-USER-01` | Complete | Use `CurrentUserProviderInterface`, `AuthenticatedUser`, and `Viewer`; do not reintroduce persistence `User` into application/domain signatures. |
| #136 `POST-READ-FE-01` | Blocked by #135 | Consumes generated list/detail clients and replaces numeric browser URLs with UUIDs. |
| #137 `POST-WRITE-BE-01` | Blocked by #135 and #126 | Reuses the Post identity, topic, persistence, and response decisions established here. |
| #140/#142 Comment slices | Later | Add Post Comment reads and frontend integration; comments are not embedded here. |
| #143/#144 Like slices | Later | Own Like mutation/viewer state; #135 returns aggregate counts only. |

Documentation work may land before #126. PHP implementation must not begin until #126 proves the canonical Docker MySQL test lane.

## 3. Scope

### In scope

- Public list, filtering, sorting, and pagination.
- Public detail by UUID with transitional positive-integer resolution.
- Post domain read model, typed criteria/results, repository port, persistence adapter, mapper, application read service, Requests, Resources, typed errors, and provider bindings.
- Author summary, Hashtag summaries, and Like/View/Comment counts.
- Locked-state visibility.
- Authenticated detail-view recording and its failure/retry semantics.
- Characterization and validation of persisted topic codes.
- Feature, unit, schema, OpenAPI, and generated-client verification.

### Non-goals

- Create, update, delete, or lock mutation endpoints.
- Comment payloads or Comment read routes.
- Like mutation or `is_liked_by_viewer` state.
- Attachments, drafts, rich text, moderation queues, notifications, new rankings, caching, or search infrastructure.
- Retiring legacy Post routes.
- Redesigning shared engagement persistence or adding a view uniqueness migration.
- A Community UI redesign.

## 4. Evidence and Current State

### Verified current flow

```text
processor-api/routes/api.php
  -> App\Http\Controllers\PostController::index|generateQuery|show
  -> App\Http\Models\Post and direct DB/model queries
  -> per-Post User, Like, View, Comment, and Hashtag lookups
  -> ad hoc { success, posts|post, requestedQuery? } JSON
  -> client/src/routes/community/PostsList/index.tsx
     or client/src/routes/community/PostDetails/index.tsx
  -> apiCall with numeric Post routes
```

`processor-api/routes/api_v1.php` contains no Post routes. `processor-api/api.json` therefore cannot generate v1 Post read clients.

### Verified persistence facts

- `posts.id` is the internal positive integer identifier.
- `posts.uuid` is non-null and unique.
- `posts.entity_type_uuid` identifies `ObjectTemplateType::POST`.
- `posts.type` is a string column containing numeric topic codes.
- `posts.locked` is boolean-like persistence state.
- Post engagement still uses legacy numeric `template_id = 9` and `real_object_id = posts.id`.
- `views` has no uniqueness constraint covering Post, user, or IP identity.

Aggregate inspection of the local development database on 2026-09-01 found two Posts, both topic code `2`, no missing UUIDs/entity-type UUIDs, no orphaned authors, no invalid topic codes, and two Post view rows. This is local evidence only; it is not a production-data guarantee.

### Current topic contradiction

The current write/filter UI defines:

| Stored code | Canonical topic label |
| --- | --- |
| 1 | Content-related |
| 2 | Off-topic |
| 3 | FAQ |
| 4 | Technical |
| 5 | Bug |
| 6 | Feedback |
| 7 | Announcement |

`PostController::getPostTypes()` contains the same seven-value order, but `index()`, `show()`, and `getPostImpressionsSearch()` map code `6` to `Announcement` and never map code `7`. The v1 contract must not preserve that enrichment defect. The canonical mapping is the persisted write/filter mapping above.

Before implementation, run this read-only query against every dataset used for verification and deployment:

```sql
SELECT type, COUNT(*) AS post_count
FROM posts
GROUP BY type
ORDER BY CAST(type AS UNSIGNED), type;

SELECT COUNT(*) AS invalid_topic_rows
FROM posts
WHERE type IS NULL
   OR type = ''
   OR type NOT IN ('1', '2', '3', '4', '5', '6', '7');

SELECT COUNT(*) AS orphaned_post_authors
FROM posts AS p
LEFT JOIN users AS u ON u.id = p.user_id
WHERE u.id IS NULL;
```

Implementation stops if invalid topics or orphaned authors exist. Do not coerce unknown topics or invent an `Unknown` API value inside #135; repair or explicitly approve the data policy first.

## 5. Current Problem and Target Shape

### Current problem in real methods

`PostController::index()`, `generateQuery()`, and `show()` combine transport, query construction, topic policy, enrichment, and view side effects:

```text
controller(request or id):
  query Post persistence rows
  for every Post:
    query author                         // N+1
    query Like count                     // N+1
    query View count                     // N+1
    query Comment count                  // N+1
    query Hashtag links and tags          // N+1
    mutate persistence model for response
    duplicate and contradict topic mapping
  on detail:
    record authenticated view             // hidden GET side effect
    embed Comments and Comment Likes      // separate migration scope
  return legacy paginator/envelope
```

This makes query cost grow with page size, hides the GET side effect, leaks persistence shapes to HTTP, and prevents a stable generated contract.

### Planned solution and boundaries

```text
routes/api_v1.php
  -> PostController
     validate/map HTTP only
  -> PostReadService
     orchestrate query, batch enrichment, and detail-view recording
  -> PostRepositoryInterface
     expose Post-oriented read operations
  -> PostRepository + PostMapper
     own Eloquent/query-builder work and persistence-to-domain mapping
  -> PostListResource or PostDetailResource
     shape the public contract only
  -> TypedResults for typed failures
```

```text
list(criteria):
  page = repository.findByCriteria(criteria)
  ids = page.items.map(internal id)
  statsById = load stats once for all ids
  hashtagsById = load hashtags once for all ids
  return typed list items + typed pagination

detail(identifier, optional authenticated user, request IP):
  post = repository.findByIdentifier(identifier)
  if missing: return PostErrors.notFound
  if authenticated:
    bestEffortIncrementView(post.id, Viewer(user id, request IP))
  stats = load stats for [post.id]
  hashtags = load hashtags for [post.id]
  return typed detail result
```

Benefits: the controller becomes a stable HTTP adapter, persistence work stays behind one port, enrichment remains bounded regardless of page size, view recording is visible in the use case, and Resources can produce an accurate OpenAPI contract without carrying legacy response machinery.

### Alternatives considered

1. **Recommended: additive feature-local v1 read module.** Introduce a thin v1 controller, a focused `PostReadService`, one repository port/adapter, one mapper, and list/detail Resources. This matches current repository direction, establishes reusable Post identity for #137/#140, and leaves legacy behavior intact during migration.
2. **Extend the legacy controller and annotate its responses.** This has the smallest initial file count, but it preserves controller-owned query/enrichment loops, persistence-shaped responses, numeric identity, and Comment coupling. OpenAPI annotations would describe the debt instead of creating the required boundary.
3. **Copy the full Article service structure.** This gives familiar folders but imports Article-specific breadth, include machinery, and existing transitional leaks that Post reads do not need. It adds more abstraction than two read endpoints currently justify.

The selected approach is option 1. It introduces only the seams required by the issue and leaves write, Comment, Like, and cache concerns for their existing slices.

## 6. Target Module Design

Use the smallest feature-local module that supports reads now and writes later without turning one service into a general coordinator.

### HTTP edge

Expected files:

- `processor-api/app/Http/v1/Community/Posts/Controllers/PostController.php`
- `processor-api/app/Http/v1/Community/Posts/Requests/IndexPostRequest.php`
- `processor-api/app/Http/v1/Community/Posts/Resources/PostListResource.php`
- `processor-api/app/Http/v1/Community/Posts/Resources/PostListItemResource.php`
- `processor-api/app/Http/v1/Community/Posts/Resources/PostDetailResource.php`
- `processor-api/app/Http/v1/Community/Posts/Resources/PostStatsResource.php`

Responsibilities:

- `PostController::index()` maps validated query values into `PostCriteriaDTO` and delegates once.
- `PostController::show()` maps the route value into `PostIdentifier`, obtains optional `AuthenticatedUser`, creates `Viewer`, delegates once, and converts failures through `TypedResults::fromError()`.
- Resources use explicit scalar casts, ISO-8601 timestamps, and no business decisions.
- Add explicit Scramble `#[Response(...)]` annotations if Resource inference does not produce the exact nested schema.

### Domain/application contracts

Expected files:

- `processor-api/app/Domain/Community/Posts/Enums/PostTopic.php`
- `processor-api/app/Domain/Community/Posts/Enums/PostSort.php`
- `processor-api/app/Domain/Community/Posts/Models/Post.php`
- `processor-api/app/Domain/Community/Posts/ValueObjects/PostIdentifier.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostCriteriaDTO.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostPageDTO.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostStatsDTO.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostListItemDTO.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostListResultDTO.php`
- `processor-api/app/Domain/Community/Posts/DTOs/PostDetailResultDTO.php`
- `processor-api/app/Domain/Community/Posts/Errors/PostErrors.php`
- `processor-api/app/Application/Community/Posts/Services/PostReadServiceInterface.php`
- `processor-api/app/Application/Community/Posts/Services/PostReadService.php`
- `processor-api/app/Application/Community/Posts/Interfaces/Repositories/PostRepositoryInterface.php`

Boundary rules:

- The domain Post contains identifiers, author summary values, content, topic, locked state, and timestamps; it contains no Eloquent model, Request, Resource, paginator, or query builder.
- `PostPageDTO` carries domain Posts plus scalar pagination metadata; a Laravel paginator must not cross the repository boundary.
- `PostReadService` owns batch-enrichment orchestration and detail-view recording.
- `PostTopic` is the single mapping for codes 1–7 and exposes its API label.
- `PostIdentifier` accepts a valid UUID or positive legacy integer and rejects all other values before repository work.
- #137 should add a focused write service/action while reusing the domain identity and repository; it should not expand `PostReadService` into CRUD.

### Persistence adapter

Expected files:

- `processor-api/app/Infrastructure/Persistence/Models/Post.php`
- `processor-api/app/Infrastructure/Persistence/Repositories/PostRepository.php`
- `processor-api/app/Infrastructure/Persistence/Repositories/PostMapper.php`

Responsibilities:

- Query `posts` with its author relation loaded in the main query.
- Resolve UUID directly; resolve positive integer only as transitional compatibility.
- Apply keyword, Hashtag, topic, sorting, and pagination in SQL.
- Map persistence rows to domain Posts before returning.
- Never return Eloquent models, builders, or `LengthAwarePaginator` through `PostRepositoryInterface`.

Keep `App\Http\Models\Post` for legacy routes. The v1 adapter is additive and must not change legacy controller behavior.

### Composition root

- Bind `PostRepositoryInterface` to `PostRepository` in `RepositoryServiceProvider`.
- Bind `PostReadServiceInterface` to `PostReadService` in the existing application-service provider unless a focused Community provider is introduced for a concrete registration need.
- Serialize edits to providers, `routes/api_v1.php`, OpenAPI generation, and Orval generation with other active backend slices.

## 7. HTTP Contract

### `GET /api/v1/posts`

#### Query parameters

| Parameter | Type | Default | Rules and semantics |
| --- | --- | --- | --- |
| `keyword` | string | none | Trimmed, maximum 255; case behavior follows the configured MySQL collation; matches title or content. |
| `hashtag` | string | none | Trimmed, maximum 100; accepts with or without leading `#`, normalizes to one leading `#`, and matches one stored Hashtag exactly. |
| `topic` | `PostTopic` integer enum | none | One of 1–7 using the canonical mapping in section 4. |
| `sort` | `newest` or `popular` | `newest` | No arbitrary database column/direction input. |
| `page` | integer | 1 | Minimum 1. |
| `per_page` | integer | 5 | Minimum 1, maximum 50. Default 5 preserves current visible page size. |

When several filters are present, they combine with AND semantics. An unmatched keyword or Hashtag returns an empty successful page, not an error. Unknown parameters may be ignored by Laravel, but documented/generated clients expose only the parameters above.

#### Sorting

- `newest`: `created_at DESC, id DESC`.
- `popular`: Post view count for `ObjectTemplateType::POST` descending, then `created_at DESC, id DESC`.
- Popular sorting includes Posts with zero views. The legacy inner-join behavior that can remove zero-view Posts is not preserved.
- Popularity means view count only. No weighted ranking is introduced.

#### Success response

```json
{
  "items": [
    {
      "id": 42,
      "uuid": "922f91b4-cbe8-4ca0-8bf8-50de48f5d086",
      "entity_type_uuid": "a4b78a83-f180-49b5-9f8a-39500cd8fabf",
      "title": "Community question",
      "topic": 3,
      "topic_label": "FAQ",
      "locked": false,
      "author": {
        "id": 7,
        "uuid": "fa088bc4-1e1b-4f5c-9876-0bc3e99b058f",
        "name": "Example user"
      },
      "hashtags": [
        { "id": 12, "content": "#grammar" }
      ],
      "stats": {
        "likes_count": 4,
        "views_count": 10,
        "comments_count": 2
      },
      "created_at": "2026-09-01T12:00:00+00:00",
      "updated_at": "2026-09-01T12:30:00+00:00"
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 5,
    "total": 1,
    "last_page": 1,
    "has_more": false
  }
}
```

List items omit full Post content and embedded Comments. Return at most the first three Hashtags in stable `hashtag_entity.id ASC` order, matching the current list presentation without loading unused values.

### `GET /api/v1/posts/{identifier}`

#### Identifier behavior

- UUID is canonical.
- A positive legacy integer is accepted during transition and resolves the same Post once.
- The response always returns the canonical UUID.
- The API does not issue a redirect. #136 compares the route identifier with the response UUID and replaces a numeric browser URL without adding a history entry.
- Malformed UUID/non-positive/non-integer input returns 422.
- A valid UUID or integer that does not resolve returns 404 through `PostErrors::notFound()`.

#### Success response

The detail response contains all list fields plus:

```json
{
  "content": "Full Post content"
}
```

Detail returns all Hashtags in stable order. It does not embed Comments, Like rows, viewer-like state, write permissions, or mutation URLs.

### Locked visibility

- Locked Posts remain present in list/search results.
- Locked Post detail remains publicly readable.
- `locked` is always emitted as a JSON boolean.
- Locking affects later Comment creation/reply behavior, not #135 read visibility.

### Error contract

- Request validation failures use the repository's normal 422 validation response.
- Missing Posts use `PostErrors::notFound()` and `TypedResults::fromError()`.
- Unexpected persistence failures are logged with identifiers but no content and return a typed 500 problem response.
- Empty collections are 200 responses with `items: []` and valid pagination metadata.

## 8. Enrichment and Query Boundaries

### Author

Load the author with the Post query and map only numeric ID, UUID, and name. Do not call a User repository per item and do not pass persistence User beyond infrastructure.

### Hashtags

Reuse the existing Hashtag persistence/application capability in one batch for the current page. Normalize its persistence-shaped output into typed Post Hashtag summaries inside the application seam before Resources receive it.

### Stats

Use application-owned batch loading for Like, View, and Comment counts scoped to `ObjectTemplateType::POST`. It may reuse `LoadEntityStatsAction`; ignore its download count because Post reads do not expose downloads. Do not execute one count query per Post.

### Query-count acceptance

The number of queries used for one list page must remain constant as the number of items on that page increases. Prove batching with a service unit test that each batch collaborator is called once with all Post IDs, or with a focused query-count regression that compares one and multiple items without asserting a fragile global absolute count.

## 9. Detail View Recording

The approved behavior preserves the legacy Post rule:

- anonymous detail reads do not create or update a view row;
- an authenticated user's first successful detail read creates one Post view row;
- a later read by the same authenticated user updates that row's timestamp and does not increase the count;
- view recording happens after the Post resolves and before stats are assembled, so a newly created view is reflected in the response count;
- a view-recording failure is logged and must not turn an otherwise successful Post read into an error.

Reuse `IncrementViewAction` only behind an authenticated-user guard in `PostReadService`. Do not change its anonymous behavior globally because Articles and Catalogues already depend on it.

### Retry and concurrency implications

The detail GET is not strictly side-effect free. Sequential client retries are count-idempotent for one authenticated user because the existing row is touched. The database has no matching unique constraint, so two concurrent first requests can race and insert duplicate rows. #135 does not add a migration or promise analytics-grade uniqueness. Document this limitation in the implementation summary; create a separately reviewed persistence task if stronger idempotency becomes required.

Do not add response caching in #135. A future cache must account for view recording instead of bypassing it accidentally.

## 10. Testing Specification

### Feature tests: `tests/Feature/Community/Posts/PostReadV1Test.php`

Cover at minimum:

1. Default list returns newest-first, deterministic pagination with five items by default.
2. Empty list returns 200 with an empty `items` array and pagination metadata.
3. Keyword matches title and content.
4. Hashtag accepts `grammar` and `#grammar`, matches exactly, and returns no rows for an unknown tag.
5. Each topic code 1–7 returns its canonical label; invalid query topics return 422.
6. Combined keyword, Hashtag, and topic filters use AND semantics.
7. Popular ordering includes zero-view Posts and uses deterministic tie breakers.
8. Authors, first-three list Hashtags, and aggregate counts are correct.
9. Locked Posts remain visible and `locked` is boolean.
10. UUID detail returns full content and all Hashtags without embedded Comments.
11. Positive-integer detail resolves the same canonical UUID.
12. Malformed identifiers return 422; unresolved valid identifiers return 404.
13. Anonymous detail does not write a view.
14. First authenticated detail creates one view and includes it in `views_count`.
15. Repeated authenticated detail keeps one row and refreshes its timestamp.

### Unit tests

- `PostTopicTest`: codes and labels exactly match the seven canonical persisted values.
- `PostReadServiceTest`: list enrichment calls each batch collaborator once; detail view recording is authenticated-only and best effort.
- `PostMapperTest`: persistence scalar casts, author mapping, topic mapping, and boolean locked state are explicit.

### Contract/schema checks

- OpenAPI exposes both routes, query enums, `PostTopic`, the list/detail resources, boolean lock state, pagination, and typed error statuses.
- Inspect `processor-api/api.json` before generating frontend code.
- Orval generates Post list/detail operations and models without handwritten frontend aliases or coercion.

## 11. Implementation Order

1. Close #126 and confirm the MySQL test lane before PHP changes.
2. Run the topic/orphan preflight queries against the intended datasets.
3. Add Post topic/sort/identifier/domain and criteria/result contracts with unit tests.
4. Add the persistence Post model, mapper, repository interface/adapter, and bindings.
5. Add `PostReadService` with batched enrichment and authenticated-only view recording.
6. Add Requests, Resources, controller, routes, typed errors, and feature tests.
7. Run focused backend tests through the dedicated Docker MySQL test runner.
8. Run `composer openapi`, inspect `api.json`, then run `npm run orval:file` sequentially.
9. Inspect generated operations/models, run frontend typecheck, and hand the stable contract to #136/#137.
10. Keep all legacy routes until their dependent frontend, Comment, Like, and retirement issues satisfy their removal gates.

## 12. Exact Verification Commands

```powershell
Set-Location processor-api
docker compose exec test-runner composer test -- tests/Feature/Community/Posts/PostReadV1Test.php
docker compose exec test-runner composer test -- tests/Unit/Community/Posts
composer openapi
rg -n '"/api/v1/posts|Post' api.json

Set-Location ../client
npm run orval:file
rg -n 'post(Index|Show)|Post' src/api/generated
npm run typecheck

Set-Location ..
./docs/scripts/validate-docs.tests.ps1
./docs/scripts/validate-docs.ps1
git diff --check
```

Run `composer openapi` before `npm run orval:file`; never run them in parallel and never hand-edit generated files.

## 13. Compatibility, Rollback, and Removal

### Compatibility

- Keep `/api/posts`, `/api/posts/search`, and `/api/post/{id}` unchanged while #136 migrates and deploys.
- Preserve legacy numeric data and engagement foreign keys.
- v1 numeric detail resolution is transitional compatibility, not the canonical identity.
- Active/generated links use UUID immediately.

### Rollback

#135 is additive and requires no data mutation. Rollback removes the v1 routes/bindings/module while legacy reads continue. View rows written by successful v1 authenticated detail reads are compatible with legacy storage and do not require rollback.

### Removal condition

Legacy Post reads and transitional numeric resolution can be removed only after:

- #136 is deployed and browser-verified with UUID navigation;
- #138, #142, and #144 no longer depend on legacy Post detail composition;
- generated contracts and active callers have been audited;
- #146 removes the remaining production `apiCall` seam; and
- #152 explicitly performs Post route retirement.

## 14. Acceptance Trace

| Issue acceptance criterion | Specification proof |
| --- | --- |
| Preserve keyword, Hashtag, topic, newest/popular, pagination, enrichment, visibility, and UUID navigation | Sections 4, 7, 8, and 10 define each behavior and its tests. |
| Characterize persisted topic mapping before defining enum | Section 4 records schema/UI/controller evidence, local aggregate results, exact preflight queries, and the stop condition. |
| Make detail view recording explicit/tested and document retry implications | Section 9 fixes authenticated-only semantics, failure handling, ordering, and concurrency limitations; section 10 names tests. |
| Keep list enrichment repository/application-owned and generated clients free of handwritten wire types | Sections 6 and 8 define the boundaries; sections 10 and 12 define OpenAPI/Orval proof. |

## 15. Review Checklist

- [x] Current route/controller/client flow was inspected.
- [x] Legacy and target behavior are separated.
- [x] Controller, application, repository, mapper, Resource, and provider responsibilities are explicit.
- [x] No persistence User crosses into application/domain contracts.
- [x] No Eloquent model, builder, or Laravel paginator crosses the Post repository boundary.
- [x] Topic codes, lock visibility, view side effects, and UUID transition are explicit.
- [x] Comments, Likes, writes, caching, and route retirement remain separate slices.
- [x] Compatibility, rollback, shared-file conflicts, tests, schema generation, and removal conditions are defined.
- [x] No unresolved design decisions remain.
