# Legacy To V1 Migration Issue Backlog

This document is the source material for a future `$to-prd` and `$to-issues` pass. It splits the legacy-to-v1 migration backlog into smaller backend and frontend issues while preserving dependency order.

The earlier discovery artifact remains at `docs/superpowers/plans/2026-05-24-legacy-v1-migration-backlog.md`, but that path is ignored by `.gitignore`. Use this tracked document as the reviewable planning artifact.

## Required Skill Usage For Future Agents

Every issue created from this document must include this skill preflight in its body:

- `$grill-with-docs`: use before planning the issue if the issue has unresolved terminology, domain behavior, or contract decisions. If the question can be answered from code, inspect code instead of asking.
- `$improve-codebase-architecture`: use for backend architecture work. Use module, interface, seam, adapter, depth, leverage, and locality language when deciding where backend behavior belongs.
- `$improve-frontend-codebase-architecture`: use for frontend route/API/module work. Prefer `route -> feature orchestration -> feature API module/hook -> generated client`; do not add frontend `domain/`, `useCases/`, `Service`, `Manager`, or generic adapter folders by default.
- `$superpowers:writing-plans`: use after the issue/PRD scope is approved and before implementation. The implementation plan should be specific to one issue, not this whole backlog.

When publishing with `$to-issues`, prefer AFK issues when the backend contract is already clear. Mark HITL only when a product/domain decision is genuinely required.

Backend planning should use the accepted **Scalable Backend Architecture** meaning from `CONTEXT.md`: a robust modular monolith that can grow in feature count, maintenance load, and moderate traffic without spreading business rules across controllers, resources, mappers, repositories, or infrastructure. Audit domain feature flows first, then module types inside the feature. Prioritize module seams before runtime mechanisms such as caching, queues, indexes, or deployment changes.

## Scope Decisions Already Made

- Article word extraction / word-processing migration is already being handled elsewhere. Do not create new issues from this document for article word extraction, article word attachment, or article word processing jobs.
- Public word list/detail migration is still in scope because it is separate from article word extraction.
- Backend and frontend should not be bundled into one huge "migrate posts to v1" issue. Split backend contract slices from frontend caller migrations unless the vertical slice is tiny and already has a generated v1 client.
- Preserve legacy endpoints until production callers are proven gone or a compatibility decision explicitly removes them.
- Prefer generated Orval clients over custom frontend wrappers when the v1 endpoint exists and the generated client is usable.
- Do not add CQRS, event-sourcing, microservice, broad cache/index/deployment, or generic infrastructure tasks from this backlog unless a later issue proves a concrete pressure.
- Keep simple CRUD simple. Add deeper application modules/actions only when they improve leverage and locality for current orchestration, side effects, authorization, enrichment, or persistence leakage.

## Backend Scalability Guardrails

Use these guardrails when extracting or implementing backend issues from this document:

- Start each backend issue with a concrete current-flow map: `route -> controller -> request -> DTO/value object -> application module/action/service -> repository/adapter -> resource -> TypedResults`.
- Controllers validate, map, and delegate. They should not compose enrichment, resolve several parent entities, coordinate stats/comments/likes, or call persistence directly.
- Application modules/actions own orchestration, authorization policy checks, transactions, side effects, include-driven enrichment, and cleanup flows.
- Resources and mappers translate shape only. Do not move business rules into resources or mappers to make response generation easier.
- Repository adapters hide persistence details. Returning Eloquent models, Laravel paginators, or query builders upward must be fixed or explicitly documented as accepted transitional debt in the issue.
- Prefer feature-local modules/actions over generic shared coordinators when responsibilities differ.
- Keep generated contract work schema-first: update backend Request/Resource/response annotations, run `composer openapi`, verify `processor-api/api.json`, then run `npm run orval:file`.

## Issue Extraction Rules

Use these when turning this document into GitHub issues:

- Create one GitHub PRD parent for the whole migration backlog, then create child issues from the list below.
- Publish child issues in dependency order so blockers can reference real issue numbers.
- Keep each issue independently implementable and reviewable.
- Backend contract issues should include schema generation and backend tests.
- Frontend migration issues should depend on backend/schema issues when the required generated client does not exist or is currently typed incorrectly.
- Include `ready-for-agent` only after the issue has clear acceptance criteria and no unresolved HITL decision.
- Do not hand-edit generated frontend files.
- Do not delete legacy backend routes in the same issue that introduces a v1 replacement unless the issue is explicitly a route retirement issue and all callers are already migrated.

## Proposed Parent PRD

**Title:** Legacy API and React route migration to v1 architecture

**Problem statement:** The application still has large non-v1 Laravel surfaces and old React route modules. This makes new work harder to place, keeps frontend code tied to raw legacy API paths, and prevents gradual retirement of `routes/api.php`.

**Solution statement:** Migrate remaining legacy areas in small backend and frontend slices. Each backend slice creates or deepens a v1 module with documented contracts and tests. Each frontend slice moves active routes to generated clients, React Query, current router hooks, and focused feature modules.

**Out of scope:** Article word extraction processing, broad UI redesign, deleting all legacy routes at once, and production infrastructure changes.

## Issue Dependency Map

```text
A0 Migration decision and skill preflight
  -> B1 Article admin/status backend
      -> F1 Article admin/dashboard frontend
  -> B2 Catalogue legacy identity backend
      -> F2 Catalogue redirect frontend
      -> F3 SavedList route and item renderer cleanup
  -> B3 Kanji schema correction backend
      -> F4 Kanji list frontend
      -> H1 Kanji detail aggregate decision
          -> B4 Kanji detail aggregate backend
              -> F5 Kanji detail frontend
  -> B5 Radicals backend
      -> F6 Radicals frontend
  -> B6 Public words backend
      -> F7 Public words frontend
  -> B7 Sentences read/detail backend
      -> F8 Sentences list/detail frontend
      -> B8 Sentence comments backend
          -> F9 Sentence comments frontend
  -> B9 Posts read backend
      -> F10 Posts list/detail read frontend
      -> B10 Posts write/moderation backend
          -> F11 Post form/edit/moderation frontend
      -> B11 Post comments backend
          -> F12 Post comments frontend
  -> B12 Generic comment delete/update backend
      -> F13 Comment API module cleanup
  -> B14 Like mutation backend seam
      -> F14 Generic like generated-client cleanup
  -> F15 apiCall retirement
      -> B13 Legacy route retirement audit
```

## Ready-To-Extract Issues

### A0: Approve Migration Sequencing And Issue-Skill Preflight

**Type:** HITL

**Blocked by:** None

**Goal:** Confirm the migration sequence, required skills, and issue rules before publishing child issues.

**Scope:**

- Confirm this document is the source of truth for `$to-prd` and `$to-issues`.
- Confirm article word extraction is excluded because it is already covered.
- Confirm backend and frontend work should be separated for large domains.
- Confirm that each implementation issue must start with the required skills listed above.
- Confirm the backend scalability guardrails are the default architecture lens for backend child issues.

**Acceptance criteria:**

- [ ] The parent PRD scope is approved.
- [ ] Child issue order is approved.
- [ ] HITL vs AFK classification is approved.
- [ ] Word extraction exclusion is explicitly preserved.
- [ ] Backend scalability guardrails are explicitly approved.

### B1: Add v1 Article Admin Status And Pending Article Contracts

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Article admin review module depth and v1 contract.

**What to build:**

Create or complete v1 article admin endpoints for pending articles and status changes. Move behavior out of the legacy article controller into the v1 article module, with thin controller methods, request validation, application-level authorization, focused application orchestration, resources, and typed errors. Prefer a feature-local article admin review action/module over expanding the general `ArticleService` unless the local implementation proves that is the smallest coherent seam.

**Acceptance criteria:**

- [ ] Admin can fetch pending articles through a documented v1 route.
- [ ] Admin can change article status through a documented v1 route.
- [ ] Non-admin users are rejected by backend authorization.
- [ ] Invalid status values are rejected by a v1 Request.
- [ ] Responses are v1-shaped and do not use legacy `{ success, data }` envelopes.
- [ ] Admin review orchestration is isolated from general article create/update/detail responsibilities.
- [ ] Backend feature tests cover success, unauthorized, invalid input, and not found.
- [ ] OpenAPI is regenerated and the new endpoints appear in `processor-api/api.json`.

### F1: Move Article Admin And Dashboard Calls To Generated v1 Clients

**Type:** AFK

**Blocked by:** B1

**Frontend focus:** Article review/dashboard API seam.

**What to build:**

Replace legacy article admin calls in the article detail review UI and dashboard pending-articles panel with generated v1 clients or focused feature API hooks. Keep route modules thin and keep cache invalidation local to the feature module/hook that owns the query. Only keep a feature API hook when it adds real query/cache behavior beyond renaming a generated client.

**Acceptance criteria:**

- [ ] Article status review no longer calls `article/{id}/setstatus`.
- [ ] Dashboard pending articles no longer calls `articles/pendinglist`.
- [ ] The frontend uses generated v1 clients or a feature API hook that adds real behavior.
- [ ] No new wrapper is added only to rename a generated client.
- [ ] Existing article review and dashboard behavior is preserved.
- [ ] Focused frontend tests and `npm run typecheck` pass.

### B2: Add v1 Catalogue Legacy Identity Compatibility Contract

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Catalogue compatibility seam.

**What to build:**

Add a small documented v1 compatibility endpoint that resolves old numeric list IDs to catalogue identity. Keep it explicit and non-shadowing so old `/list/:id` frontend redirects can move off `/api/list/{id}` without deleting compatibility. This is only a compatibility resolver, not a new catalogue detail enrichment path or generic identity abstraction.

**Acceptance criteria:**

- [ ] A v1 route resolves legacy numeric list ID to `{ id, uuid, title }`.
- [ ] The route cannot be shadowed by `catalogues/{uuid}`.
- [ ] Invalid IDs and missing catalogues return typed v1 errors.
- [ ] Visibility rules match current compatibility behavior or are explicitly documented.
- [ ] The compatibility module does not absorb catalogue detail, item, stats, cleanup, or view side-effect responsibilities.
- [ ] Backend feature tests cover found, not found, invalid ID, and visibility.
- [ ] OpenAPI and Orval can generate a usable client.

### F2: Move Catalogue Legacy Redirects To v1 Compatibility Client

**Type:** AFK

**Blocked by:** B2

**Frontend focus:** Catalogue legacy redirect adapter.

**What to build:**

Replace `client/src/api/catalogues/legacyCatalogues.ts` raw `/list/{id}` lookup with the generated v1 compatibility client. Keep current redirects for `/lists`, `/newlist`, `/list/:catalogueId`, and `/list/edit/:catalogueId`.

**Acceptance criteria:**

- [ ] `CatalogueLegacyRedirects` no longer depends on `/api/list/{id}`.
- [ ] `legacyCatalogues.ts` is deleted or becomes a thin generated-client module with a removal condition.
- [ ] Failed resolution still shows the current "not found or deleted" UX.
- [ ] Focused frontend tests cover successful legacy redirect and failed resolution.
- [ ] `npm run typecheck` passes.

### F3: Remove Dead SavedList Routes And Rehome Catalogue Item Renderers

**Type:** AFK

**Blocked by:** F2

**Frontend focus:** Catalogue item module locality.

**What to build:**

Delete unrouted SavedList route files and move still-used SavedList item renderers into catalogue-oriented modules. Preserve catalogue detail item rendering and remove `@ts-nocheck` from touched files.

**Acceptance criteria:**

- [ ] Unrouted SavedList route files are removed after import verification.
- [ ] Active item renderers live under catalogue-oriented names.
- [ ] Catalogue detail still renders article, kanji, radical, word, and sentence items.
- [ ] Touched item modules have explicit prop types and no `@ts-nocheck`.
- [ ] Component tests cover the catalogue item variants.

### B3: Fix v1 Kanji OpenAPI Schema For Generated Clients

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Kanji resource schema interface.

**What to build:**

Correct v1 kanji response annotations/resources so Orval generates structured kanji types instead of `string`. Do not change user-visible kanji behavior in this issue. Treat the generated type mismatch as a backend schema/resource problem first.

**Acceptance criteria:**

- [ ] `KanjiResource` schema exposes structured fields.
- [ ] Kanji collection schema exposes structured list and pagination fields.
- [ ] Public scalar fields are explicitly typed or cast so Scramble/Orval do not infer degraded wire types.
- [ ] Backend schema/unit tests prove the generated OpenAPI shape.
- [ ] `composer openapi` updates `processor-api/api.json`.
- [ ] `npm run orval:file` generates structured kanji client types.

### F4: Migrate Kanji List Route To v1 Query Module

**Type:** AFK

**Blocked by:** B3

**Frontend focus:** Kanji list route/module seam.

**What to build:**

Rewrite the kanji list route as a function component using a typed query module/hook over the generated v1 kanji client. Move filter and pagination mapping out of JSX into a local route helper or feature API module.

**Acceptance criteria:**

- [ ] `KanjisList` has no `@ts-nocheck`.
- [ ] `KanjisList` has no class component state.
- [ ] The route no longer calls `apiCall`.
- [ ] Search/filter/pagination behavior maps to v1 query params.
- [ ] React Query owns server state.
- [ ] Focused tests cover initial load, filters, pagination/load more, and empty state.

### H1: Decide v1 Kanji Detail Aggregate Shape

**Type:** HITL

**Blocked by:** F4

**Goal:** Decide whether kanji detail should remain lean or expose related words, sentences, and articles through include flags.

**Recommended answer:** Use include flags so list/detail contracts stay separate and callers explicitly ask for heavy related data.

**Acceptance criteria:**

- [ ] Decision records whether kanji detail uses include flags.
- [ ] Decision records which related data is required by current UI.
- [ ] Decision records whether article stats/hashtags remain part of kanji detail.
- [ ] If needed, create or update `CONTEXT.md` with domain terms only, not implementation details.

### B4: Add v1 Kanji Detail Aggregate Includes

**Type:** AFK

**Blocked by:** H1

**Backend focus:** Kanji detail aggregate module.

**What to build:**

Extend v1 kanji detail to preserve current detail UX through explicit include flags. Keep enrichment behind application/repository modules, not in the controller. Return application read shapes that keep Laravel paginator/query-builder details out of the domain/application interface.

**Acceptance criteria:**

- [ ] Kanji detail supports approved include flags.
- [ ] Related words, sentences, and articles are loaded through application/repository modules.
- [ ] Article stats/hashtags are shaped consistently with article v1 patterns if included.
- [ ] Resources remain presentational and do not decide include behavior or business rules.
- [ ] Response schema is documented and generated.
- [ ] Backend tests cover lean detail, each include, invalid identifier, and not found.

### F5: Migrate Kanji Detail Route To v1 Detail And Catalogue Clients

**Type:** AFK

**Blocked by:** B4

**Frontend focus:** Kanji detail route/module seam.

**What to build:**

Rewrite kanji detail to use the generated v1 kanji detail client and existing v1 catalogue for-item clients. Keep catalogue add/remove behavior stable.

**Acceptance criteria:**

- [ ] `KanjiDetails` no longer calls `/api/kanji/{id}`.
- [ ] The route uses generated v1 kanji and catalogue clients.
- [ ] `react-bootstrap` modal usage is replaced with shared modal/dialog primitives.
- [ ] `@ts-nocheck` is removed.
- [ ] Existing related words/sentences/articles display remains available.
- [ ] Focused tests cover detail load, catalogue membership, add/remove, and unauthenticated redirect.

### B5: Add v1 Radicals List And Detail Contracts

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Radical module creation.

**What to build:**

Create a v1 radicals module following the kanji module shape where it fits. Include list/search and detail behavior needed by the current frontend, including related kanjis if still required. Keep the module read-focused and use application/repository read shapes instead of exposing framework pagination or query builders as the module interface.

**Acceptance criteria:**

- [ ] `/api/v1/radicals` exists and is documented.
- [ ] `/api/v1/radicals/{identifier}` exists and is documented.
- [ ] Domain/application/infrastructure modules provide a clear seam for radical retrieval.
- [ ] List/detail result shapes expose typed pagination and related data without leaking persistence or framework objects upward.
- [ ] Backend tests cover list, search/filter, detail, related kanjis, invalid identifier, and not found.
- [ ] OpenAPI and Orval generate usable radical clients.

### F6: Migrate Radical Routes To v1

**Type:** AFK

**Blocked by:** B5

**Frontend focus:** Radical list/detail modules.

**What to build:**

Rewrite radical list/detail routes as typed function components using generated v1 radical clients and existing v1 catalogue for-item clients.

**Acceptance criteria:**

- [ ] `RadicalsList` no longer uses `@ts-nocheck`, class state, or `apiCall`.
- [ ] `RadicalDetails` no longer calls `/api/radical/{id}`.
- [ ] Catalogue add/remove uses the generated v1 catalogue item clients.
- [ ] `react-bootstrap` modal usage is removed.
- [ ] Focused tests cover list behavior, detail behavior, and catalogue membership.

### B6: Add v1 Public Words List And Detail Contracts

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Public word read module.

**What to build:**

Add v1 word list/detail endpoints for public browsing. Reuse the existing word domain/persistence mapper work where possible. Do not include article word extraction processing in this issue. Keep public word browsing as a read-contract module, not a back door into article word extraction or processing orchestration.

**Acceptance criteria:**

- [ ] `/api/v1/words` exists and is documented.
- [ ] `/api/v1/words/{identifier}` exists and is documented.
- [ ] Legacy display fields such as meaning, furigana, and word types remain available in a typed resource shape.
- [ ] Include flags handle related articles and kanjis if needed.
- [ ] List/detail result shapes expose typed pagination and include data without leaking persistence or framework objects upward.
- [ ] Backend tests cover list, search/filter, detail, includes, invalid identifier, and not found.
- [ ] OpenAPI and Orval generate usable word clients.

### F7: Migrate Public Word Routes To v1

**Type:** AFK

**Blocked by:** B6

**Frontend focus:** Word list/detail modules.

**What to build:**

Rewrite word list/detail routes to use generated v1 word clients and React Query. Keep catalogue membership behavior stable through the existing v1 catalogue for-item module.

**Acceptance criteria:**

- [ ] `WordsList` no longer uses `@ts-nocheck`, class state, `next_page_url`, or `apiCall`.
- [ ] `WordDetails` no longer calls `/api/word/{id}`.
- [ ] Word detail keeps related articles/kanjis behavior approved by B6.
- [ ] Catalogue add/remove uses generated v1 catalogue clients.
- [ ] Existing word detail tests are updated to the new seam.
- [ ] `npm run typecheck` passes.

### B7: Add v1 Sentences List And Detail Contracts

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Sentence read module.

**What to build:**

Add v1 sentence list/detail endpoints for public browsing. Keep sentence create/update/delete out unless a separate decision says those write flows are product-active. Keep sentence reads as a focused module with request validation, application query orchestration, repository retrieval, resources, and typed errors.

**Acceptance criteria:**

- [ ] `/api/v1/sentences` exists and is documented.
- [ ] `/api/v1/sentences/{identifier}` exists and is documented.
- [ ] Detail supports approved related kanjis/words behavior.
- [ ] List/detail result shapes expose typed pagination and related data without leaking persistence or framework objects upward.
- [ ] Backend tests cover list, search/filter, detail, includes, invalid identifier, and not found.
- [ ] OpenAPI and Orval generate usable sentence clients.

### F8: Migrate Sentence List And Detail Reads To v1

**Type:** AFK

**Blocked by:** B7

**Frontend focus:** Sentence list/detail read modules.

**What to build:**

Rewrite sentence list/detail read behavior to use generated v1 sentence clients and React Query. Keep comment migration separate.

**Acceptance criteria:**

- [ ] `SentencesList` no longer uses `@ts-nocheck`, class state, `next_page_url`, or `apiCall`.
- [ ] `SentenceDetails` no longer calls `/api/sentence/{id}` for the base detail.
- [ ] Sentence detail preserves approved related kanjis/words display.
- [ ] Comment wiring is not expanded in this issue except to avoid breaking the page.
- [ ] Focused tests cover list and detail read behavior.

### B8: Add v1 Sentence Comment Reads

**Type:** AFK

**Blocked by:** B7

**Backend focus:** Sentence comments seam.

**What to build:**

Add v1 comment read support for sentences, consistent with existing article/catalogue comment reads or a generic entity-comment read contract if that decision has been made. Move parent lookup, entity validation, and comment-list orchestration into application-level modules so the controller remains a thin route adapter.

**Acceptance criteria:**

- [ ] Sentence comments can be read through a documented v1 route.
- [ ] Missing sentence returns a typed not-found error.
- [ ] Comment list pagination and include-likes behavior matches existing v1 comment patterns.
- [ ] The controller does not build entity-specific `CommentResource` arrays or coordinate parent lookup details.
- [ ] Backend tests cover success, not found, pagination, and include-likes.
- [ ] OpenAPI and Orval generate a usable client.

### F9: Move Sentence Comments To Current CommentsBlock Contract

**Type:** AFK

**Blocked by:** B8

**Frontend focus:** Sentence comment module/caller seam.

**What to build:**

Update sentence detail comments to use current `CommentsBlock` props and v1 read/write clients. Remove legacy `objectId`, `objectType`, and `initialComments` usage from sentence detail.

**Acceptance criteria:**

- [ ] Sentence detail passes `readObjectType`, `readObjectUuid`, `entityId`, `entityType`, and `entityUuid`.
- [ ] Comment writes use generic v1 `POST /api/v1/comments`.
- [ ] Comment reads use the v1 sentence comment route.
- [ ] Legacy comment props are removed from sentence detail.
- [ ] Focused tests cover read, add, delete if supported, and locked/unauthenticated states if applicable.

### B9: Add v1 Community Post List And Detail Read Contracts

**Type:** AFK

**Blocked by:** A0

**Backend focus:** Post read module.

**What to build:**

Create v1 post list/detail/search endpoints for public community browsing. Keep create/update/delete/lock/comment work separate. Put search/filter/visibility/detail composition in application modules, not in controllers or resources.

**Acceptance criteria:**

- [ ] `/api/v1/posts` exists and is documented.
- [ ] `/api/v1/posts/{identifier}` exists and is documented.
- [ ] Search/filter behavior matches current community list behavior.
- [ ] Detail exposes fields currently needed by `PostDetails` except write/moderation actions.
- [ ] Read orchestration has a focused module interface with typed criteria/results and no persistence leakage to HTTP resources.
- [ ] Backend tests cover list, search/filter, detail, locked post visibility, invalid identifier, and not found.
- [ ] OpenAPI and Orval generate usable post read clients.

### F10: Migrate Community Post List And Detail Reads To v1

**Type:** AFK

**Blocked by:** B9

**Frontend focus:** Community read route modules.

**What to build:**

Rewrite `PostsList` and post detail read loading to use generated v1 post clients and React Query. Leave form/edit/moderation for B10/F11 and comments for B11/F12.

**Acceptance criteria:**

- [ ] `PostsList` no longer uses `@ts-nocheck`, class state, `next_page_url`, or `apiCall`.
- [ ] `PostDetails` no longer calls `/api/post/{id}` for the base detail.
- [ ] Search/filter and pagination behavior remains available.
- [ ] Post detail renders current read-only fields from the v1 shape.
- [ ] Focused frontend tests cover list and detail read behavior.

### B10: Add v1 Community Post Write And Lock Moderation Contracts

**Type:** AFK

**Blocked by:** B9

**Backend focus:** Post write/moderation module.

**What to build:**

Add v1 post create/update/delete and lock moderation routes with request validation and authorization. Keep comments separate. If ownership writes and admin lock moderation have different reasons to change, keep their application actions/modules separate behind the same v1 route family.

**Acceptance criteria:**

- [ ] Authenticated users can create posts through v1.
- [ ] Owners can update/delete their posts through v1.
- [ ] Unauthorized users cannot update/delete posts they do not own.
- [ ] Admin users can lock/unlock posts through v1.
- [ ] Non-admin users cannot lock/unlock posts.
- [ ] Ownership, policy checks, transactions, and moderation side effects live in application modules/actions, not controllers or resources.
- [ ] Backend tests cover success, unauthorized, invalid payload, not found, and lock moderation.
- [ ] OpenAPI and Orval generate usable post write clients.

### F11: Migrate Post Form, Edit, Delete, And Lock UI To v1

**Type:** AFK

**Blocked by:** B10, F10

**Frontend focus:** Community write/moderation route modules.

**What to build:**

Rewrite post form/edit/delete/lock UI to use React Router v6 hooks, generated v1 clients, and shared modal primitives. Keep comments separate.

**Acceptance criteria:**

- [ ] `PostForm` no longer uses `@ts-nocheck`, class component, `props.history`, or `apiCall`.
- [ ] `PostEdit` no longer uses `@ts-nocheck`, `componentWillMount`, `props.match`, `props.history`, or `apiCall`.
- [ ] Post delete uses a shared delete modal pattern.
- [ ] Post lock/unlock uses generated v1 client and respects admin-only UI gating.
- [ ] Focused tests cover create, edit load, submit, delete, lock/unlock, and unauthorized behavior where practical.

### B11: Add v1 Community Post Comment Reads

**Type:** AFK

**Blocked by:** B9

**Backend focus:** Post comments seam.

**What to build:**

Add v1 comment read support for posts, consistent with existing article/catalogue comment reads or the approved generic comment-read contract. Move parent lookup, lock-state behavior, and comment-list orchestration into application-level modules so the controller remains a thin route adapter.

**Acceptance criteria:**

- [ ] Post comments can be read through a documented v1 route.
- [ ] Missing post returns typed not-found.
- [ ] Locked post behavior is explicit.
- [ ] The controller does not build entity-specific `CommentResource` arrays or coordinate parent lookup details.
- [ ] Backend tests cover success, not found, pagination, include-likes, and locked behavior.
- [ ] OpenAPI and Orval generate a usable client.

### F12: Move Post Comments To Current CommentsBlock Contract

**Type:** AFK

**Blocked by:** B11, F10

**Frontend focus:** Post comment caller seam.

**What to build:**

Update post detail comments to use current `CommentsBlock` props and v1 read/write clients. Remove legacy initial-comments behavior from post detail.

**Acceptance criteria:**

- [ ] `PostDetails` passes current `CommentsBlock` props.
- [ ] Post comment reads use the v1 post comment route.
- [ ] Post comment writes use generic v1 comment create.
- [ ] Old `objectId`, `objectType`, and `initialComments` props are not used.
- [ ] Focused tests cover comment read and add behavior.

### B12: Add v1 Comment Delete And Optional Update Contract

**Type:** AFK

**Blocked by:** B8, B11

**Backend focus:** Comment mutation module.

**What to build:**

Add v1 comment delete support and only add update support if active UI or near-term product scope requires it. Keep generic create unchanged. Put mutation policy checks, parent constraints, and repository calls behind a focused comment mutation application module.

**Acceptance criteria:**

- [ ] Comment delete exists through a documented v1 route.
- [ ] Owners and admins can delete according to current policy.
- [ ] Unauthorized users are rejected.
- [ ] Missing comments return typed not-found.
- [ ] Update route is either implemented with tests or explicitly left out of scope.
- [ ] Generic comment create keeps the validated `{ entity_type, entity_id, entity_uuid }` tuple contract and does not reintroduce string-name resolver indirection.
- [ ] Backend tests cover delete success, unauthorized, not found, and parent entity constraints if relevant.
- [ ] OpenAPI and Orval generate usable mutation clients.

### F13: Replace Comment API Legacy Delete And Custom Read Construction

**Type:** AFK

**Blocked by:** B12, F9, F12

**Frontend focus:** Comment API module depth.

**What to build:**

Deepen `client/src/api/comments.ts` so callers do not construct legacy URLs or know transport-specific comment details. Prefer generated query/mutation helpers where possible, and keep `CommentsBlock` as the main route-facing interface.

**Acceptance criteria:**

- [ ] Comment delete no longer builds legacy URLs.
- [ ] Comment reads use generated clients where all active entity types support them.
- [ ] Generated client types come from the regenerated backend schema, not local hand-written request/response aliases.
- [ ] Query keys are stable and local to the comment module or generated helpers.
- [ ] `CommentsBlock` remains the main interface for route callers.
- [ ] Focused comment API and `CommentsBlock` tests pass.

### B14: Deepen v1 Like Mutation Backend Seam

**Type:** AFK

**Blocked by:** B1, B2, B9 as needed by active like callers

**Backend focus:** Like mutation application seam.

**What to build:**

Move generic v1 like toggling behind a repository-backed application module/action. Keep the current `/api/v1/like-instance` behavior stable, but remove direct persistence-model and mapper use from generic engagement mutation logic. Keep the route/controller/request thin and preserve the existing public contract unless a schema correction is explicitly required.

**Acceptance criteria:**

- [ ] Like toggle behavior still supports article, catalogue, comment, and post callers that are active at the time of implementation.
- [ ] `EngagementService` or its replacement no longer directly queries `PersistenceLike` for toggle behavior.
- [ ] Like persistence goes through `LikeRepositoryInterface` or an equivalent repository/application seam.
- [ ] The controller delegates to an application module/action and does not contain persistence or mapping logic.
- [ ] Response schema is documented and generated.
- [ ] Backend tests cover like, unlike, invalid entity/type, unauthorized, and missing entity behavior where applicable.
- [ ] OpenAPI and Orval generate a usable like mutation client.

### F14: Normalize Generic Likes Around Generated v1 Client

**Type:** AFK

**Blocked by:** B14, B1, B2, B9 as needed by callers

**Frontend focus:** Like API module depth.

**What to build:**

Replace raw axios use for `/v1/like-instance` with generated v1 client usage. Keep a helper only if it creates real leverage by hiding repeated mapping for article, catalogue, comment, and post callers.

**Acceptance criteria:**

- [ ] No raw axios call remains for `/v1/like-instance`.
- [ ] Callers use generated request/response types.
- [ ] The remaining like helper, if any, has a small interface and hides repeated mapping.
- [ ] Article, catalogue, comment, and post callers keep their current like behavior.
- [ ] Focused tests cover request mapping and optimistic/cache behavior where relevant.

### F15: Retire `apiCall` From Production Frontend Code

**Type:** AFK

**Blocked by:** F1, F2, F4, F5, F6, F7, F8, F10, F11, F13

**Frontend focus:** Legacy frontend adapter retirement.

**What to build:**

Remove remaining production usage of `client/src/services/api.ts` after feature migrations have replaced active callers. Keep only typed transitional adapters for intentionally remaining legacy endpoints.

**Acceptance criteria:**

- [ ] `rg -n "apiCall" client/src` has no production route/component callers.
- [ ] Any remaining legacy endpoint access lives in a named typed adapter with target v1 replacement and removal condition.
- [ ] Console request/response logging from `apiCall` is gone from production paths.
- [ ] Tests no longer mock `apiCall` for migrated behavior.
- [ ] `npm run typecheck` passes.

### B13: Audit And Retire Unused Legacy Backend Routes By Domain

**Type:** AFK

**Blocked by:** F15

**Backend focus:** Legacy route retirement.

**What to build:**

Audit `routes/api.php` after frontend callers have moved. Retire unused legacy routes in small domain-specific commits or issues, preserving compatibility routes that still have a documented reason to exist.

**Acceptance criteria:**

- [ ] Each legacy route is classified as retired, intentionally compatible, or still blocked.
- [ ] Retired routes have no production frontend caller.
- [ ] Compatibility routes name their v1 replacement and removal condition.
- [ ] Route removal is grouped by domain, not one broad delete-all PR.
- [ ] Backend route/feature tests cover compatibility routes that remain.

## Suggested `$to-prd` User Stories Seed

Use these as source material for the PRD user-story list:

1. As a learner, I want Japanese material pages to keep working while the backend moves to v1, so that migration does not interrupt study workflows.
2. As a learner, I want kanji, radical, word, and sentence lists to search and paginate consistently, so that browsing Japanese material feels predictable.
3. As a learner, I want detail pages to preserve related words, kanjis, sentences, articles, and catalogue membership where available, so that I do not lose context during migration.
4. As a community user, I want posts to keep list/detail/create/edit/delete behavior while moving to v1, so that the community area remains usable.
5. As a signed-in user, I want comments and likes to behave consistently across articles, catalogues, sentences, and posts, so that interaction patterns do not change per content type.
6. As an admin, I want article review and post lock moderation to use v1 contracts, so that moderation behavior is tested and consistently authorized.
7. As a future agent, I want every migration issue to state its backend and frontend dependencies, so that I can pick up one issue without rediscovering the entire migration.
8. As a maintainer, I want legacy routes retired only after callers are migrated, so that cleanup does not break old links or active UI.

## Suggested `$to-issues` Slicing Guidance

When `$to-issues` is run, do not merge these into larger tickets:

- Do not merge post read, post write, post comments, and post frontend work into one issue.
- Do not merge kanji schema correction with kanji detail aggregate work.
- Do not merge sentence reads with sentence comments.
- Do not merge public word list/detail with article word extraction.
- Do not merge the like backend seam work with frontend generated-client cleanup.
- Do not merge `apiCall` retirement with the feature migrations that unblock it.

Good issue labels:

- `ready-for-agent` after HITL blockers are resolved.
- `backend` for B-series issues.
- `frontend` for F-series issues.
- `architecture` for issues that create/deepen modules or change seams.
- `needs-decision` for A0 and H1 until approved.

## Verification Defaults

Backend issues:

```powershell
cd processor-api
docker compose up -d --build db-test test-runner
docker compose exec test-runner composer test -- tests/Feature/<Area>
docker compose exec test-runner composer test -- tests/Unit/<Area>
docker compose exec laravel-app composer openapi
```

Frontend issues:

```powershell
npm run orval:file
npm run typecheck
npm run test -- <focused-test-file>
```

If Vitest/Vite fails with `spawn EPERM` from `esbuild` inside the sandbox, rerun the same command outside the sandbox before treating it as an application failure.
