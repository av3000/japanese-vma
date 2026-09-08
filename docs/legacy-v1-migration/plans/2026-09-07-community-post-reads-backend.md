# Community Post v1 Public Reads — Implementation Plan

| Field | Value |
| --- | --- |
| Issue | [#135 — POST-READ-BE-01](https://github.com/av3000/japanese-vma/issues/135) |
| Parent | [#125](https://github.com/av3000/japanese-vma/issues/125) |
| Source specification | [`docs/legacy-v1-migration/specs/community-post-reads-backend.md`](../specs/community-post-reads-backend.md) |
| Plan written | 2026-09-07 |
| Validated against | `origin/develop` @ `15f1ab0` (`feat(api): add v1 article moderation endpoints (#234)`) |
| Worktree / branch | `.worktrees/issue-135-post-reads-plan` / `feature/issue-135-post-reads-plan` |
| Track | Backend only |

**Goal:** add public `GET /api/v1/posts` and `GET /api/v1/posts/{identifier}` with typed filters, deterministic sorting, bounded batch enrichment, explicit locked state, authenticated-only best-effort view recording, and named OpenAPI component schemas Orval can generate from — while leaving every legacy Post route, controller, and model untouched.

**Why this plan replaces the 2026-09-02 draft:** that draft targeted `55ec089`, before `#233` (sentence authoring) and `#234` (article moderation) merged. Those two PRs settled the conventions this slice must copy: typed Resources that produce **named** component schemas, module-owned typed errors, `Result` + `TypedResults` at the controller edge, and an `api.json` contract test. This plan also drops three abstractions the earlier draft invented (`PublicIdentifier`, `ObjectTemplateIdResolver`, a shared query-cast trait) because develop already has working equivalents or does not need them yet.

---

## 1. Verified current state

Legacy reads (untouched by this slice):

- `processor-api/routes/api.php:156` — `GET posts` → `PostController@index`
- `processor-api/routes/api.php:157` — `GET post/{id}` → `PostController@show`
- `processor-api/routes/api.php:158` — `POST posts/search` → `PostController@generateQuery`
- `processor-api/app/Http/Controllers/PostController.php:37` — `index()` paginates 5, then does per-post N+1 lookups for likes/views/comments/hashtags/author and builds a `postsByTopic` bucket map
- `PostController.php:94` — `show()` records a view, embeds comments **and** per-comment likes, and maps topic labels inline
- `PostController.php:238` — `getPostImpressionsSearch()` repeats the same enrichment plus Japanese date-part strings, and truncates hashtags to the first 3
- `PostController.php:286` — `sortByViewsTotal()` uses `leftJoin('views')` **plus** `where('views.template_id', …)`, which degrades to an inner join and drops zero-view posts; its `select('posts.*')` + `groupBy('posts.id')` shape is a MySQL-ism that is no longer valid on PostgreSQL
- `PostController.php:305` — `generateQuery()`: a `keyword` starting with `#` becomes a hashtag lookup of the **first token only** (`getUniquehashtagPosts`, `:752`), otherwise `Post::whereLike(['title','content'])`; `filterType` (`20` = all) filters `posts.type`; `sortByWhat` is `new` / `pop`; page size fixed at 5
- `PostController.php:404` — `incrementView()` writes a view **only for authenticated users**, matches on `user_id` alone, and refreshes `updated_at` when a row already exists

`processor-api/routes/api_v1.php` has **no** Post routes, and `processor-api/api.json` contains no `/posts` path.

Persistence facts (verified in migrations):

- `posts` = `id`, `user_id`, `type` (**string** holding numeric topic codes), `title`, `content`, `locked` (boolean), timestamps (`2020_05_21_201045`)
- `posts.uuid` added nullable + indexed (`2025_09_29_065804`), then made non-null unique (`2025_09_30_185528`, `2025_10_05_201410`)
- `posts.entity_type_uuid` added **nullable with no backfill** (`2025_09_29_065813`)
- engagement rows key on `template_id` = `objecttemplates.id`; `ObjectTemplatesTableSeeder` inserts rows with `id = ObjectTemplateType::getLegacyId()`, so `post` = `9` in seeded and test data
- hashtag storage was renamed: `hashtag_entity` (`entity_type_id`, `entity_id`, `hashtag_id`, soft-deleted) + `uniquehashtags`
- there is **no** `App\Infrastructure\Persistence\Models\Post` and **no** `PostFactory`

The topic contradiction stands: the write/filter vocabulary is 1 Content-related, 2 Off-topic, 3 FAQ, 4 Technical, 5 Bug, 6 Feedback, 7 Announcement (`PostController::getPostTypes()`, `:19`), but `index()`, `show()`, and `getPostImpressionsSearch()` all label code `6` as *Announcement* and never map `7`. v1 ships the canonical mapping and does not reproduce the defect.

## 2. Seams on develop to reuse (do not re-invent)

| Need | Reuse | Location |
| --- | --- | --- |
| Batch likes/views/comments counts | `LoadEntityStatsAction::batchLoadStatsById(int $templateId, array $ids)` | `app/Application/Engagement/Actions/LoadEntityStatsAction.php` |
| Batch hashtags | `HashtagServiceInterface::getBatchHashtags(array $ids, ObjectTemplateType)` / `getHashtags(int $id, ObjectTemplateType)` | `app/Application/Engagement/Services/HashtagService.php:26,45` |
| View side effect | `IncrementViewAction::execute(int $id, ObjectTemplateType, Viewer)` | `app/Application/Engagement/Actions/IncrementViewAction.php` |
| Best-effort view wrapper | `ArticleService::trackView()` (`try`/`catch` + `Log::error`) | `app/Application/Articles/Services/ArticleService.php:197` |
| Current user / viewer | `CurrentUserProviderInterface`, `AuthenticatedUser`, `Viewer` | `app/Application/Auth/…`, `app/Domain/Shared/ValueObjects/Viewer.php` |
| Result / error transport | `Result`, `ResultError`, `TypedResults::fromError()` | `app/Shared/Results/`, `app/Shared/Http/TypedResults.php` |
| Pagination | `Pagination` VO + `PaginationResource` (`page, per_page, total, last_page, has_more`) | `app/Domain/Shared/ValueObjects/Pagination.php`, `app/Http/v1/Shared/Resources/PaginationResource.php` |
| Author / hashtag wire shapes | `AuthorResource`, `HashtagResource` | `app/Http/v1/Shared/Resources/`, `app/Http/v1/Engagement/Resources/` |
| Stats wire shape | `EngagementStatsSummaryResource` → `EngagementStatsResource` | `app/Http/v1/Engagement/Resources/` |
| Identifier handling | `EntityId::isValid()` + `ctype_digit()` branch, exactly as `SentenceService::findByIdentifier()` (`:36`) | `app/Application/JapaneseMaterial/Sentences/Services/SentenceService.php` |
| Slice shape to copy end-to-end | the Sentence v1 module (controller → request → criteria → service → repository → mapper → resources) | `app/{Http/v1,Application,Domain,Infrastructure}/…/Sentences/` |

**Deliberately not built here:** a `PublicIdentifier` value object (four services already inline the two-branch check; extracting a shared VO is its own cross-cutting slice), an `ObjectTemplateIdResolver` (Task 0 proves `objecttemplates.post.id = 9`, and `HashtagRepository::resolveTemplateId()` already resolves-with-fallback on the hashtag path), and a shared query-integer-cast trait (four `IndexRequest`s duplicate a private helper; deduplicating them is unrelated cleanup).

## 3. Decisions to confirm

| # | Decision | Recommendation | Cost to flip |
| --- | --- | --- | --- |
| D1 | Malformed identifier status. Spec §7 says 422; every v1 sibling returns **400** via `…Errors::invalidIdentifier()`. | Return **400**, matching siblings. | One error constant + one assertion. |
| D2 | Stats envelope. Spec §7 proposes flat `stats: {likes_count, views_count, comments_count}`; `ArticleResource` emits `engagement: { stats: {…} }` including `downloads_count`. | Reuse `EngagementStatsSummaryResource` so the generated client gets one shared model; `downloads_count` is always `0` for Posts. | One Resource + response assertions. |
| D3 | Topic labels. Legacy labels code `6` *Announcement* and ignores `7`. | Ship the canonical 1–7 mapping; the contract change is intentional and documented. | — (the AC requires it). |
| D4 | Popular sort and zero-view posts. Legacy inner-join drops them. | Include them, ordering `views_total DESC, created_at DESC, id DESC`; the legacy shape is also invalid SQL on PostgreSQL. | — |
| D5 | Anonymous view recording. Legacy Posts record only for authenticated users; Articles also record anonymous views. | Keep Post behavior (authenticated-only). The divergence over the shared `views` table is a product-policy question for a later engagement slice. | One `if` + one test. |
| D6 | Keyword case sensitivity. Legacy MySQL `LIKE` was case-insensitive; the PostgreSQL migration silently made every v1 `LIKE` case-sensitive. | Use `ILIKE` for Post keyword search so "preserve keyword" actually holds. Note — but do not fix here — that Article/Catalogue/Kanji/Radical/Sentence repositories still carry the drift. | One where-clause + one test. |
| D7 | Module namespace. develop has no `Community/` folder; Comments/Likes sit at `App\Http\v1\{Comments,Engagement}`. | Follow the spec: `Community\Posts\…` in all four layers so `#137`/`#140` land beside it. | Namespace rename. |
| D8 | `entity_type_uuid` in the payload — the column is nullable and was never backfilled. | Emit `ObjectTemplateType::POST->value` from the enum, not the column. No backfill migration in this slice. | — |

## 4. Target contract

### `GET /api/v1/posts` (public)

| Param | Type | Default | Rules |
| --- | --- | --- | --- |
| `keyword` | string | — | trimmed, 1–255; case-insensitive match on `title` **or** `content` |
| `hashtag` | string | — | trimmed, 1–100; accepted with or without a leading `#`, normalized to exactly one `#`, exact match on one stored tag |
| `topic` | int enum | — | one of `PostTopic` 1–7 |
| `sort` | `newest` \| `popular` | `newest` | closed enum; no raw column/direction input |
| `page` | int | 1 | min 1 |
| `per_page` | int | 5 | 1–50; default 5 preserves the legacy visible page size |

Filters combine with AND. No match is `200` with `items: []` and valid pagination metadata. Locked posts stay in results.

Response: `{ items: PostResource[], pagination: PaginationResource }`. Each item carries `id`, `uuid`, `entity_type_uuid`, `title`, `topic`, `topic_label`, `locked` (boolean), `author` (`AuthorResource`), `hashtags` (first 3, stable `hashtag_entity.id ASC`), `engagement.stats`, `created_at`, `updated_at` (ISO-8601, `format('c')`). List items omit `content` and never embed comments.

### `GET /api/v1/posts/{identifier}` (public)

- UUID is canonical; a positive integer resolves once during transition; the response always returns the UUID (no redirect — `#136` rewrites the browser URL).
- Invalid input → `400` (D1). Valid but unresolvable → `404` via `PostErrors::notFound()`.
- All list fields **plus** `content`, and **all** hashtags in stable order.
- Recording: when the caller is authenticated, `IncrementViewAction` runs inside `try`/`catch`; a failure is logged and the `200` still returns.
- Never included: comments, like rows, viewer-like state, permissions, mutation URLs.

## 5. File map

| File | Responsibility |
| --- | --- |
| `app/Domain/Community/Posts/Enums/PostTopic.php` | canonical int-backed 1–7 topic + `label()` |
| `app/Domain/Community/Posts/Enums/PostSort.php` | `newest` \| `popular` |
| `app/Domain/Community/Posts/Models/Post.php` | persistence-free read model (ids, author summary, title, content, topic, locked, timestamps) |
| `app/Domain/Community/Posts/Models/PostStats.php` | zero-filled counts consumed by `EngagementStatsResource` |
| `app/Domain/Community/Posts/Queries/PostQueryCriteria.php` | `Pagination` + keyword/hashtag/topic/sort, with keyword/hashtag normalization |
| `app/Domain/Community/Posts/DTOs/PostPageDTO.php` | repository output: raw domain Posts + pagination (keeps enrichment out of the port) |
| `app/Domain/Community/Posts/DTOs/PostListItemDTO.php` | one domain Post + its stats + its hashtags |
| `app/Domain/Community/Posts/DTOs/PostListResultDTO.php` | service output: enriched `items` + pagination array |
| `app/Domain/Community/Posts/DTOs/PostDetailResultDTO.php` | Post + stats + hashtags |
| `app/Domain/Community/Posts/Errors/PostErrors.php` | `notFound()`, `invalidIdentifier()` |
| `app/Application/Community/Posts/Interfaces/Repositories/PostRepositoryInterface.php` | read port |
| `app/Application/Community/Posts/Services/PostReadServiceInterface.php`, `PostReadService.php` | orchestration, batch enrichment, view side effect |
| `app/Infrastructure/Persistence/Models/Post.php` | v1 Eloquent model + `author()` + casts |
| `app/Infrastructure/Persistence/Repositories/PostMapper.php` | persistence row → domain Post |
| `app/Infrastructure/Persistence/Repositories/PostRepository.php` | filters, sorting, pagination, UUID/legacy-id lookup |
| `app/Http/v1/Community/Posts/Requests/IndexPostRequest.php` | typed query validation + normalization |
| `app/Http/v1/Community/Posts/Controllers/PostController.php` | map → delegate → Resource/`TypedResults` |
| `app/Http/v1/Community/Posts/Resources/PostListItemResource.php`, `PostDetailResource.php`, `PostListResource.php` | named component schemas; list item and detail are separate so only detail advertises `content` |
| `app/Http/v1/Engagement/Resources/EngagementStatsResource.php`, `EngagementStatsSummaryResource.php` | **edit:** widen union to accept `PostStats` |
| `app/Infrastructure/Persistence/Repositories/HashtagRepository.php` | **edit:** `orderBy('id')` on both lookups so hashtag order is deterministic |
| `routes/api_v1.php` | **edit:** two public routes |
| `app/Providers/RepositoryServiceProvider.php`, `ArticlesServiceProvider.php` | **edit:** two bindings |
| `database/factories/PostFactory.php` | UUID-backed posts for DB tests |
| `processor-api/api.json` | **regenerated** |
| `tests/Unit/Community/Posts/PostTopicTest.php`, `PostMapperTest.php`, `PostReadServiceTest.php` | vocabulary, mapping, enrichment/view rules |
| `tests/Unit/PostReadsOpenApiTest.php` | named-component + path assertions on `api.json` |
| `tests/Feature/Community/Posts/PostReadV1Test.php` | end-to-end contract (the file the issue names) |
| `tests/Feature/Community/Posts/PostRepositoryTest.php` | DB-backed filter/sort/pagination behavior |

Three classes will be named `Post` (`App\Http\Models\Post`, `App\Domain\Community\Posts\Models\Post`, `App\Infrastructure\Persistence\Models\Post`). Alias them `DomainPost` / `PersistencePost` in every file that imports more than one, as `SentenceRepository` does.

---

## Task 0: Preflight — lane, data characterization, template id

**Files:** none. Attach evidence to the PR.

- [ ] Boot only the dedicated test lane from `processor-api/`:

```bash
docker compose up -d --build db-test test-runner
docker compose exec test-runner composer test:prepare
```

- [ ] Run the characterization queries against **every** dataset used for verification and deployment (dev DB and, before release, production). PostgreSQL syntax:

```sql
SELECT type, COUNT(*) AS post_count FROM posts GROUP BY type ORDER BY NULLIF(type, '')::int NULLS LAST, type;
SELECT COUNT(*) AS invalid_topic_rows FROM posts WHERE type IS NULL OR type = '' OR type NOT IN ('1','2','3','4','5','6','7');
SELECT COUNT(*) AS orphaned_authors FROM posts p LEFT JOIN users u ON u.id = p.user_id WHERE u.id IS NULL;
SELECT COUNT(*) AS posts_missing_uuid FROM posts WHERE uuid IS NULL;
SELECT COUNT(*) AS posts_missing_entity_type FROM posts WHERE entity_type_uuid IS NULL;
SELECT id, title, entity_type_uuid FROM objecttemplates WHERE title = 'post';
SELECT indexname, indexdef FROM pg_indexes WHERE tablename = 'views' AND indexdef ILIKE '%template_id%';
```

**Gates:**
- Any invalid topic code or orphaned author → **stop** and get an explicit data-policy decision. Do not coerce unknown topics and do not invent an `Unknown` API value inside this issue (spec §4).
- `objecttemplates` `post` row id must be `9`. If it is not, `LoadEntityStatsAction` (called with `ObjectTemplateType::POST->getLegacyId()`) and `HashtagRepository::resolveTemplateId()` (which reads the table) would disagree — stop and add the resolver the earlier draft proposed.
- `posts.entity_type_uuid` nulls are expected and are fine under D8; record the count.
- No `(template_id, real_object_id)` index on `views` → open a follow-up performance issue; do **not** slip an unreviewed index migration into this slice.

- [ ] Record dataset name, timestamp, base commit, and every count in the PR description.

## Task 1: Domain vocabulary (test-first)

**Files:** create `PostTopic`, `PostSort`, `PostErrors`, `tests/Unit/Community/Posts/PostTopicTest.php`

- [ ] `PostTopic: int` — `CONTENT_RELATED = 1`, `OFF_TOPIC = 2`, `FAQ = 3`, `TECHNICAL = 4`, `BUG = 5`, `FEEDBACK = 6`, `ANNOUNCEMENT = 7`, with `label(): string` (`match`, like `ObjectTemplateType::label()`).
- [ ] `PostSort: string` — `NEWEST = 'newest'`, `POPULAR = 'popular'`, plus `public const DEFAULT = self::NEWEST`.
- [ ] `PostErrors` mirroring `SentenceErrors`: `notFound(string $identifier)` → `POST_NOT_FOUND` / 404; `invalidIdentifier()` → `INVALID_POST_IDENTIFIER` / 400 (D1).
- [ ] Unit test: all seven codes map to the canonical labels; `PostTopic::tryFrom(0)`, `tryFrom(8)`, `tryFrom(20)` are `null`; `6` is **Feedback** and `7` is **Announcement** (the anti-regression assertion for the legacy defect).

**Verify:** `docker compose exec test-runner composer test -- tests/Unit/Community/Posts/PostTopicTest.php`

## Task 2: Persistence model, factory, mapper, domain model

**Files:** create `Infrastructure/Persistence/Models/Post.php`, `database/factories/PostFactory.php`, `Domain/Community/Posts/Models/{Post,PostStats}.php`, `Infrastructure/Persistence/Repositories/PostMapper.php`, `tests/Unit/Community/Posts/PostMapperTest.php`

- [ ] Persistence `Post` modeled on `Infrastructure/Persistence/Models/Article.php` (the factory-bearing example — `Sentence.php` has no factory): `$table = 'posts'`, `HasFactory` + explicit `newFactory(): PostFactory`, a `@property` PHPDoc block, `author()` → `belongsTo(User::class, 'user_id')`, casts `locked` → `bool` and timestamps → `datetime`. `type` stays a string column; the mapper casts it.
- [ ] `PostFactory` following `ArticleFactory`: valid UUID, `entity_type_uuid = ObjectTemplateType::POST->value`, a topic code in 1–7, `locked = false`, `user_id => User::factory()`; add states `locked()`, `topic(PostTopic $topic)`, and `byUser(User $user)`.
- [ ] Domain `Post`: readonly, constructor-promoted, exposing `getIdValue()`, `getUuid(): EntityId`, `getTitle()`, `getContent()`, `getTopic(): PostTopic`, `isLocked()`, `getAuthorId()/getAuthorUuid()/getAuthorName()`, `getCreatedAt()/getUpdatedAt()`. No Eloquent, builder, Request, Resource, or paginator.
- [ ] `PostStats` mirroring `ArticleStats` (`likes/downloads/views/comments`, zero defaults) so it can satisfy `EngagementStatsResource`.
- [ ] `PostMapper::mapToDomain(PersistencePost $post): DomainPost` — casts `type` via `PostTopic::from((int) $post->type)`, casts `locked` to bool, and reads the eager-loaded author. A row whose topic is outside 1–7 must throw (Task 0 already proved none exist); do not silently default.
- [ ] Unit test the mapper with a non-persisted model instance: string `'3'` becomes `PostTopic::FAQ`, `locked` `0`/`1` become `false`/`true`, an unmapped topic throws.

**Verify:** `docker compose exec test-runner composer test -- tests/Unit/Community/Posts/PostMapperTest.php`

## Task 3: Repository port and adapter

**Files:** create `PostRepositoryInterface`, `PostRepository`, `PostQueryCriteria`, `PostListResultDTO`, `tests/Feature/Community/Posts/PostRepositoryTest.php`

- [ ] Port (three methods only):

```php
public function find(PostQueryCriteria $criteria): PostListResultDTO;
public function findByUuid(EntityId $uuid): ?Post;
public function findByLegacyId(int $id): ?Post;
```

- [ ] `PostQueryCriteria` copies `SentenceQueryCriteria`: `public const DEFAULT_PER_PAGE = 5`, a `Pagination` VO, plus `?string $keyword`, `?string $hashtag`, `?PostTopic $topic`, `PostSort $sort`, and a `forListing(...)` named-argument factory.
- [ ] `PostRepository::find()` — always `->with('author')`, then:
  - `keyword` → `where(fn => where('title','ILIKE',"%kw%")->orWhere('content','ILIKE',"%kw%"))` (D6)
  - `hashtag` → `whereIn('posts.id', <subquery over hashtag_entity joined to uniquehashtags on the normalized '#tag', filtered by entity_type_id = post template id, whereNull('deleted_at')>)`
  - `topic` → `where('type', (string) $topic->value)` (string column)
  - `sort = newest` → `orderByDesc('created_at')->orderByDesc('id')`
  - `sort = popular` → `selectSub()` a correlated `COUNT(*)` over `views` (`whereColumn('views.real_object_id','posts.id')`, `template_id = 9`) aliased `views_total`, then `orderByDesc('views_total')->orderByDesc('created_at')->orderByDesc('id')`. This keeps zero-view posts (D4) and avoids the legacy `groupBy` that PostgreSQL rejects.
  - paginate with `$criteria->pagination->per_page` / `->page`, map every row through `PostMapper`, and return `PostListResultDTO` with the same five pagination keys `SentenceRepository` returns.
- [ ] No Eloquent model, builder, or `LengthAwarePaginator` crosses the port.
- [ ] DB-backed feature test (`RefreshDatabase` + `SeedsBaselineData`, like `IndexArticleTest`): keyword hits title and content and is case-insensitive; hashtag matches with and without `#` and excludes soft-deleted links; topic filters exactly; `newest` and `popular` orderings are deterministic; a zero-view post still appears under `popular`; pagination metadata is correct on page 2; locked posts are present; an unmatched filter yields an empty page.

**Verify:** `docker compose exec test-runner composer test -- tests/Feature/Community/Posts/PostRepositoryTest.php`

## Task 4: Read service — enrichment and the view side effect

**Files:** create `PostReadServiceInterface`, `PostReadService`, `PostListItemDTO`, `PostDetailResultDTO`, `tests/Unit/Community/Posts/PostReadServiceTest.php`

- [ ] Interface:

```php
public function find(PostQueryCriteria $criteria): Result;                       // PostListResultDTO
public function findByIdentifier(string $identifier, Viewer $viewer): Result;    // PostDetailResultDTO
```

- [ ] `find()` — call the repository once, collect `$ids`, then exactly two enrichment calls: `LoadEntityStatsAction::batchLoadStatsById(ObjectTemplateType::POST->getLegacyId(), $ids)` and `HashtagServiceInterface::getBatchHashtags($ids, ObjectTemplateType::POST)`. Zero-fill missing stats into `PostStats`, slice hashtags to the first 3 in stable id order, and return `PostListItemDTO`s. Skip both calls when the page is empty.
- [ ] `findByIdentifier()` — resolve identity exactly as `SentenceService::findByIdentifier()` (`EntityId::isValid()` → `findByUuid`; `ctype_digit && > 0` → `findByLegacyId`; otherwise `PostErrors::invalidIdentifier()`), return `PostErrors::notFound($identifier)` when unresolved, then:
  1. if `$viewer->isAuthenticated()`, call `IncrementViewAction` inside `try`/`catch` and `Log::error` on failure (copy `ArticleService::trackView()`); anonymous callers record nothing (D5);
  2. load stats and all hashtags for the single id;
  3. return `PostDetailResultDTO`.
- [ ] Order matters: the view is recorded **before** stats are read, so the caller's own view is reflected in `views_count` — assert this explicitly, since it is the behavior `#136` will see.
- [ ] Unit test with mocked port and actions: list issues one stats call and one hashtag call regardless of page size (bounded enrichment); an empty page issues none; unknown ids get zero-filled stats; hashtags are capped at 3 for list and uncapped for detail; the view is recorded once for an authenticated viewer, never for an anonymous one, and a throwing `IncrementViewAction` still yields a success `Result`.

**Verify:** `docker compose exec test-runner composer test -- tests/Unit/Community/Posts/PostReadServiceTest.php`

## Task 5: HTTP edge — request, controller, resources, routes, bindings

**Files:** create `IndexPostRequest`, `PostController`, `PostResource`, `PostListResource`; edit `EngagementStatsResource`, `EngagementStatsSummaryResource`, `routes/api_v1.php`, `RepositoryServiceProvider`, `ArticlesServiceProvider`

- [ ] `IndexPostRequest` modeled on `IndexSentenceRequest`: `authorize(): true`; `prepareForValidation()` casts `page`/`per_page`/`topic` to integers and normalizes `hashtag` to a single leading `#`; rules `page` `integer|min:1`, `per_page` `integer|min:1|max:50`, `keyword` `string|min:1|max:255`, `hashtag` `string|min:1|max:100`, `topic` `Rule::enum(PostTopic::class)`, `sort` `Rule::enum(PostSort::class)`.
- [ ] `PostController::index()` maps validated input into `PostQueryCriteria::forListing(...)`, delegates once, and returns `PostListResource` (or `TypedResults::fromError()`).
- [ ] `PostController::show(string $identifier, Request $request)` builds `new Viewer($this->currentUserProvider->currentAuthenticatedUser()?->id, (string) $request->ip())`, delegates once, and returns `PostResource` or `TypedResults::fromError()`.
- [ ] `PostResource extends JsonResource` with `public static $wrap = null`, a typed constructor (`DomainPost`, `?PostStats`, `array $hashtags`, `bool $includeContent = false`), an array-shape PHPDoc return type, and **explicit scalar casts** — `(bool) locked`, `(int) topic`, `format('c')` timestamps — per `AGENTS.md` §5. `entity_type_uuid` comes from `ObjectTemplateType::POST->value` (D8).
- [ ] `PostListResource` mirrors `SentenceListResource`/`ArticleListResource`: `items` mapped from `PostListItemDTO` plus `new PaginationResource(...)`.
- [ ] Widen `EngagementStatsResource` and `EngagementStatsSummaryResource` unions to `ArticleStats|CatalogueStats|PostStats` (D2). This is the only shared-file edit in the slice — serialize it against other in-flight backend work.
- [ ] Routes in the **public** block of `routes/api_v1.php`, beside the Sentence pair:

```php
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{identifier}', [PostController::class, 'show']);
```

No concrete `posts/...` sibling exists yet, so no ordering hazard like `articles/pending` — but keep future concrete routes above the parameterized one.
- [ ] Bind `PostRepositoryInterface` → `PostRepository` in `RepositoryServiceProvider` and `PostReadServiceInterface` → `PostReadService` in `ArticlesServiceProvider` (where every service binding currently lives).
- [ ] Feature test `tests/Feature/Community/Posts/PostReadV1Test.php` — the file the issue names. Cover: 200 list shape and pagination defaults (`per_page` 5); each filter and both sorts; `per_page=51` and `topic=8` and `sort=oldest` are 422 validation problems; UUID detail returns `content` and all hashtags; numeric detail resolves the same post and returns the canonical UUID; unknown UUID and unknown integer → 404; `posts/not-a-uuid` → 400 (D1); a locked post is visible with `locked: true`; an authenticated detail request creates exactly one `views` row and a second request updates rather than duplicates it; an anonymous detail request creates none; no response contains a `comments` key.

**Verify:** `docker compose exec test-runner composer test -- tests/Feature/Community/Posts/PostReadV1Test.php`

## Task 6: OpenAPI contract and generated client

**Files:** regenerate `processor-api/api.json`; create `tests/Unit/PostReadsOpenApiTest.php`

- [ ] Annotate both controller actions with `#[Response(type: 'PostListResource')]` / `#[Response(type: 'PostResource')]` so Scramble emits **named** component schemas rather than inline shapes — the exact defect `#234` fixed for moderation. Add an explicit `#[Response(type: 'array{...}')]` only if a nested shape still collapses.
- [ ] Regenerate: `docker compose exec test-runner composer openapi` (or `composer openapi` on the host), and commit the `api.json` diff.
- [ ] `tests/Unit/PostReadsOpenApiTest.php`, modeled on `tests/Unit/ArticleModerationOpenApiTest.php` (a plain `PHPUnit\Framework\TestCase` reading `api.json` from disk): assert `paths['/posts']` and `paths['/posts/{identifier}']` exist; assert both `200` responses `$ref` `#/components/schemas/PostListResource` and `#/components/schemas/PostResource`; assert `PostTopic` and `PostSort` are emitted as reusable enum schemas; assert the list operation documents `keyword`, `hashtag`, `topic`, `sort`, `page`, `per_page` as query parameters.

  **Note:** Scramble strips the route prefix, so paths are `/posts`, **not** `/api/v1/posts`. The issue's `rg -n '"/api/v1/posts|Post' api.json` check will not match; use `rg -n '"/posts' api.json`.
- [ ] Regenerate the client and typecheck:

```bash
cd client && npm run orval:file && npm run typecheck
```

`client/src/api/generated/` is gitignored (`client/.gitignore:35`), so nothing is committed from this step — it only proves the contract generates and compiles. Confirm the generated output contains `getPosts`/`getPostsIdentifier` hooks and real models (no `unknown`/anonymous types), which satisfies "generated clients need no handwritten wire types".

## Task 7: Quality gates and PR

- [ ] `./format-changed.ps1` from `processor-api/` **on the host** (not in a container — `pint --dirty` sees no `.git` there and falsely passes).
- [ ] `docker compose exec test-runner composer stan`.
- [ ] Full targeted run:

```bash
docker compose exec test-runner composer test -- tests/Feature/Community/Posts tests/Unit/Community/Posts tests/Unit/PostReadsOpenApiTest.php
```

- [ ] Legacy regression check — legacy Post routes still resolve and are untouched:

```bash
docker compose exec test-runner php artisan route:list --path=post
git diff --stat origin/develop -- routes/api.php app/Http/Controllers/PostController.php app/Http/Models/Post.php   # must be empty
```

- [ ] PR description records: Task 0 evidence, the D1–D8 decisions as taken, the intentional contract changes (topic 6/7 labels, zero-view posts under `popular`, case-insensitive keyword), and the `api.json` diff summary.

---

## 6. Verification commands (corrected from the issue)

```powershell
Set-Location processor-api
docker compose up -d --build db-test test-runner
docker compose exec test-runner composer test -- tests/Feature/Community/Posts/PostReadV1Test.php
docker compose exec test-runner composer openapi
rg -n '"/posts' api.json                      # issue says '"/api/v1/posts' — Scramble strips the prefix
Set-Location ../client
npm run orval:file
rg -n 'getPosts|Post' src/api/generated       # gitignored output; generation + typecheck is the gate
npm run typecheck
```

## 7. Risks and rollback

| Risk | Handling |
| --- | --- |
| `objecttemplates.post.id ≠ 9` in production | Task 0 gate; stats and hashtag paths would otherwise disagree |
| Invalid persisted topic codes | Task 0 gate; the mapper throws rather than inventing a value |
| `posts.entity_type_uuid` nulls | D8 — emit from the enum; no backfill in this slice |
| PostgreSQL rejects the legacy `popular` join | replaced by a correlated `selectSub` count |
| Shared-file contention | only `routes/api_v1.php`, the two providers, and the two Engagement resources; serialize with other in-flight backend slices, and regenerate `api.json` last |
| Detail is not comment-complete | `#136` cannot fully migrate `PostDetails` until `#140`/`#142`; the FE issue must keep legacy comment loading until then |

**Rollback:** delete the additive routes, module, and bindings; regenerate `api.json`. There is no migration and no legacy mutation, so no data change to reverse.

## 8. Out of scope (unchanged from the issue)

Post writes, lock mutation, Comments, Likes and viewer-like state, ranking or caching design, retirement of legacy Post routes, a `views` uniqueness migration, and any Community UI redesign.

---

## 9. As-built notes (2026-09-08)

The slice is implemented on this branch. Decisions D1–D8 were taken as recommended. What differed from the plan above, and why:

1. **Repository returns `PostPageDTO`, not `PostListResultDTO`.** The port stays free of enrichment: it hands back domain Posts + pagination, and `PostReadService` produces the enriched `PostListResultDTO`.
2. **One Resource became two.** A single `PostResource` with an `includeContent` flag produced a schema where `content` was **required**, which the list response does not satisfy. `PostListItemResource` (no `content`) and `PostDetailResource` (with `content`) each emit an accurate named schema.
3. **Scramble needs an inline `@var` hint above array entries.** `'items' => $items` and `'hashtags' => $hashtags` only resolve to `$ref`s when preceded by `/** @var array<int, X> */` and assigned to a variable carrying the same annotation (this is why `ArticleModerationListResource` is written that way). A payload built inside a trait method, or an `array_map(...)` inlined directly into the returned array, silently degrades to `items: {}`.
4. **`HashtagResource::collection()` was replaced with `array_map`.** `collection()` returns `AnonymousResourceCollection`, which contradicts the `array<int, HashtagResource>` docblock — that mismatch is baselined for `ArticleResource`, and this slice avoids adding to the baseline.
5. **`LoadEntityStatsAction::batchLoadStatsById()` declares `string $templateId`.** Existing callers rely on coercion; `PostReadService` has `strict_types=1`, so it casts explicitly. The action's signature is wrong for the data (it is compared to an integer column) but was left alone to keep the diff focused.
6. **Extra shared-file edit:** `HashtagRepository::findAllByFilter()` / `findAllByEntityIds()` now `orderBy('id')`. Neither ordered before, so "stable `hashtag_entity.id ASC` order" was not achievable without it. Articles and Catalogues get the same determinism.
7. **The mapper rejects a null `created_at`.** `posts.created_at` is nullable; the contract types it as a required string. Add `SELECT COUNT(*) FROM posts WHERE created_at IS NULL` to the Task 0 preflight.

### Worktree/lane setup this required

- `processor-api/docker-compose.yml` and `format-changed.ps1` both use **fixed container names** (`laravel_test_runner`, `pgsql_test_db`, `laravel-app`), so they bind to whichever worktree started them. Run this branch's lane under its own project: `docker compose -p jvma135 -f docker-compose.test.yml up -d --build db-test redis test-runner`, and run Pint through the same project rather than `format-changed.ps1`.
- A fresh worktree has no `storage/oauth-*.key`; every Passport-touching request 500s until `php artisan passport:keys` runs in the container.
- After adding classes, run `composer dump-autoload -o` inside the container — the optimized classmap will not see new files otherwise.

### Verification performed

| Check | Result |
| --- | --- |
| `composer test -- tests/Feature/Community/Posts tests/Unit/Community/Posts tests/Unit/PostReadsOpenApiTest.php` | 49 tests, 231 assertions, pass |
| `composer openapi` | `/posts`, `/posts/{identifier}`; named `PostListResource`, `PostListItemResource`, `PostDetailResource`, `PostTopic`, `PostSort` |
| `npm run orval:file` + `npm run typecheck` | generates `postIndex`/`postShow` + hooks and typed models; typecheck clean |
| Pint on changed files | 11 style issues fixed |
| PHPStan (scoped config) | no errors |
| `composer stan` (whole app) | **fails on clean `develop` too** — `phpstan-baseline.neon` references deleted files (`KanjiCollectionResource.php`, `AuthSessionService.php`). Pre-existing; not from this slice. |

### Task 0 evidence (local dev database, 2026-09-07)

`objecttemplates` `post` row id = **9**, matching `ObjectTemplateType::POST->getLegacyId()` — the resolver the earlier draft proposed is not needed. Zero invalid topic codes, orphaned authors, missing UUIDs, or missing `entity_type_uuid`. **The local `posts` table is empty**, so topic distribution is uncharacterized: re-run the Task 0 queries against production before deploying. `views` carries only its primary-key index — no `(template_id, real_object_id)` coverage; open a follow-up performance issue rather than adding a migration here.
