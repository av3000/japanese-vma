# Architecture Comparison Backlog

> **Status:** Triage backlog; every row is an open question until a decision row links to an ADR or a rule in `processor-api/AGENTS.md`
> **Last reviewed:** 2026-09-11
> **Evidence baseline:** This repository's working tree on branch `feature/issue-141-comment-core-fe` (with `develop` merged in), and a read-only survey of two production .NET modular monoliths the v1 architecture borrows from, referred to below as **Project K** and **Project B**. Neither is part of this repository, and they are described only at the level of layer and abstraction, never by name or path.
> **Audience:** Maintainers deciding architecture direction, and contributors learning why the v1 backend is shaped the way it is

## Purpose

Japanese VMA's v1 backend borrows patterns from two much larger, production-grade .NET modular monoliths. This document lists, one row per area, how those solutions solve a problem, how this repository solves it today, and the question that still needs an informed decision. It exists so that pattern choices are compared deliberately rather than adopted because a sibling module happened to do it first.

It is a **triage list, not a decision record**. When a row is decided:

1. If the decision is hard to reverse, surprising without context, and the result of a real trade-off, write an ADR (an `adr` folder under `docs`, created with the first record) and link it from the row.
2. If it is an enforceable coding rule, add it to `processor-api/AGENTS.md` and link the section from the row.
3. Otherwise record the outcome in the row's **Status** column and move on.

Rows are independent; pick any two to compare in one sitting. The recommended shape for a comparison session is *problem in plain words → how the reference solutions solve it → how this repo solves it → trade-offs → recommendation and the conditions that would flip it*.

## How to read the columns

- **Reference solutions** — what Project K and/or Project B actually do, located by layer (Domain, Application, Infrastructure, API host) rather than by file. Where the two differ, both are named.
- **This repository** — what exists today, with a path in this repository. Uses the evidence labels from [`docs/README.md`](../README.md): *verified* means read in code during this review; *inferred* means supported by configuration or documentation but not exercised.
- **Question** — the decision to make for a Laravel modular monolith at this project's scale.
- **Status** — `Open`, `Researching`, `Decided (link)`, or `Not applicable (reason)`.

## Read and write model

| # | Area | Reference solutions | This repository | Question | Status |
|---|---|---|---|---|---|
| 1 | List reads: one repository per aggregate vs. separate Reader | Both: one repository interface per aggregate, in the Application layer's persistence interfaces, holding both a get-by-id method **and** search/filter methods that return an envelope, never the enriched entity. No MediatR, no CQRS, no read-repository or finder abstraction. | Repository for identity reads and writes, `*ReaderInterface` for paginated list reads (verified: `processor-api/app/Application/Articles/Interfaces/Readers/ArticleListReaderInterface.php`, rule in `processor-api/AGENTS.md` Application section). Split is a direction, not complete: `ArticleRepositoryInterface::findModerationQueue` still returns a paginator wrapper. | Both shapes agree the entity is built complete and list-only data lives on the envelope. Given that, is the extra Reader port worth its file count per module, or only for heavy reads (recursive CTEs, facets)? | Decided for Comments in PR #294 follow-up work (Reader); ADR pending |
| 2 | Read-model materialisation | Project K projects list rows at the database into an immutable record struct that lives in the Domain layer, and never loads the entity for a list. Project B returns full entities inside its generic search-result envelope. | Readers map every row through the module's `*Mapper` into the domain model, then the service wraps it in `*ListItemDTO` (verified: `processor-api/app/Infrastructure/Persistence/Readers/DatabaseArticleListReader.php`). | When is loading the entity for a list too expensive, and should a `*ListItemDTO` ever be filled straight from a projection instead of from the entity? | Open |
| 3 | Envelope shape | Project B: one generic `SearchResult<T>` in the Domain layer carrying items, total hits and facets. Project K: several competing paged-result types. | Named DTO pair per module: `*PageDTO` (raw reader output) and `*ListResultDTO` (enriched), plus `*PaginationDTO` per module; no shared pagination DTO (verified: `processor-api/app/Domain/Articles/DTOs/`). | One shared generic envelope in `Domain/Shared`, or keep a named pair per module and accept five identical pagination DTOs? | Open |
| 4 | Query composition and count/item consistency | Project B: static composable query helpers over the ORM's deferred query type, grouped in a queries folder per aggregate; count and page are cut from the same expression. No Specification objects. | `ArticleListFilterBuilder` shared by the reader and the facet counters so items and counts cannot drift (verified). Comments will add `CommentDescendantsQueryBuilder` for the recursive CTE. | Should every module with facets or counts have a named filter builder shared by items and counts, and is that a rule for `AGENTS.md`? | Open |
| 5 | Visibility and soft delete | Project B: an explicit include-deleted read method beside the default read. | `ArticleVisibilityScope` value object passed to every reader call (verified); moderation status visibility is an open product decision in the Articles packet. | Is visibility a scope object, a method variant, or a query default, and who is allowed to widen it? | Open |
| 6 | Caching around reads | Project B: cache-reader decorators (about thirty) wrapping read interfaces; Project K applies the same idea to its feature-toggle reads. | No read-side cache layer; Redis is used for queue and cache coordination only (inferred from `processor-api/config/cache.php`, `queue.php`). | If caching arrives, is it a decorator on a `*ReaderInterface`, which is only possible if the read port exists, or inside the repository? Feeds row 1. | Open |

## Domain model

| # | Area | Reference solutions | This repository | Question | Status |
|---|---|---|---|---|---|
| 7 | Entity construction and immutability | Both: entities constructed complete; Project B uses positional records, Project K constructor-only classes with get-only properties. The only `With*` methods found return new instances for domain state, never list counts. | Domain models are constructor-built by `*Mapper`; PR #294 introduced `Comment::withReplies()` with placeholder constructor defaults, which the follow-up removes. | Rule candidate for `AGENTS.md`: domain models are constructed complete; per-read enrichment lives in `*ListItemDTO`, never as defaulted constructor arguments plus a wither. | Decided for Comments; rule and ADR pending |
| 8 | Strongly typed identifiers | Project B: value-type identifiers per aggregate cross module boundaries. | `EntityId`, `UserId` value objects in `Domain/Shared/ValueObjects` (verified); legacy numeric ids still travel beside UUIDs. | Where are identifiers validated, which id may cross a module boundary, and when does the legacy numeric id retire? | Open |
| 9 | Mapping persistence to domain | Both: hand-written mapper classes (over one hundred in Project K); no mapping library. | Static `*Mapper::mapToDomain` per module (verified: `processor-api/app/Infrastructure/Persistence/Repositories/CommentMapper.php`, `ArticleMapper.php`). | Static class vs. injected mapper (Articles injects, Comments calls statically); one translation per module reused by repository and reader. | Open |
| 10 | Validation placement | Project K: pure static Application-layer validators returning a list of error strings against a data-driven schema; no validation library, no constructor guards. | HTTP `FormRequest` classes per endpoint (verified); value objects guard some invariants; repositories occasionally re-check (Comments `SORTABLE_COLUMNS`). | Three places validate today. Which layer owns which check, and should repositories ever re-validate what a typed criteria object already makes unrepresentable? | Open |

## Application layer and module boundaries

| # | Area | Reference solutions | This repository | Question | Status |
|---|---|---|---|---|---|
| 11 | Failure signalling | Project K: typed exceptions per bounded context, one per HTTP status, converted by a global exception handler in the API host into RFC 7807 `application/problem+json`. No Result type. | `Result::success/failure` with typed error classes at the service boundary, rendered by `TypedResults::fromError`; PR #294 adds `ProblemDetailsResource` so the schema documents the body (verified). | Return value vs. exception for expected failures, and should every v1 error body be one documented Problem Details component so Orval generates a single error type? | Open |
| 12 | Authorization placement | Thin in both: framework policy builders only in one admin area of Project B; most checks live in application services. | Policy objects per module (`CommentPolicy`, `ArticlePolicy`) used by both the write gate and the response's viewer flags (verified). | Keep policy objects as the single source for "can this viewer do X", and should the UI-hint use and the write-gate use be required to share them by rule? | Open |
| 13 | Command/query split in services | Both: one application service per aggregate handles reads and writes. | Articles splits `ArticleService` (single-item use cases) from `ArticleListService` (list use case) (verified). Comments has one `CommentService`. | Is a separate `*ListService` the rule whenever a module has a Reader, or only when list enrichment is heavy? | Open |
| 14 | Cross-module calls and enforcement | Project B: a contracts project per module is the only thing other modules may reference, enforced by the compiler. Project K: an interfaces folder per module acts as its public surface, by convention. | Rule: anything another module calls is a `*ServiceInterface` returning `Result`; Domain purity enforced by `tests/Unit/Architecture/DomainLayerDependencyTest.php` (verified). Nothing enforces the cross-module rule itself. | Add an architecture test (or Deptrac) for module-to-module imports, or keep it convention? | Open |
| 15 | Dependency registration per module | Project B: each module owns its service-registration extension and its own settings file; a separate dependency-injection project is the only place that references persistence, cache or search. | `RepositoryServiceProvider`, `ArticlesServiceProvider`, `PdfServiceProvider` and others under `processor-api/app/Providers/` (verified); one `config/` tree. | One provider per module as the rule, or a shared repository provider plus module providers only when a module has non-trivial wiring? | Open |
| 16 | Transactions and side effects | Neither solution surfaces a Unit of Work or an outbox. Project K keeps a persisted message log around its outbound service-bus messages for audit and idempotency. | `DB::transaction` inside services plus queued jobs (`ProcessArticleKanjisJob`) dispatched after commit (verified). | How is "the write committed but the job never ran" detected today, and is a persisted processing state (Articles has `DatabaseArticleProcessingStateReader`) the general answer? | Open |

## Platform and delivery

| # | Area | Reference solutions | This repository | Question | Status |
|---|---|---|---|---|---|
| 17 | Contract generation | OpenAPI UI in development only; no generated client; no API versioning package. | Scramble builds `processor-api/api.json`, Orval generates the TypeScript client; `CommentContractOpenApiTest` pins parts of the schema (verified). | This repository is ahead here. Question is only whether contract tests should exist per module as a rule. | Open |
| 18 | Configuration validation at boot | Both: options pattern with fail-fast at startup; the process refuses to boot on partial configuration. | `.env` keys read at request time through `config()` (inferred); a fresh worktree without Passport keys fails on the first auth request, not at boot. | Add a boot-time config check (custom Artisan health check or provider assertion) for keys the app cannot run without? | Open |
| 19 | Observability | Project B: OpenTelemetry logs, metrics and traces wired through shared service defaults, per-module meters and health endpoints. | Telescope, Horizon and Sentry configured (inferred from `processor-api/config/`); no correlation id or business metrics found. | Correlation id through request, job and log, and per-module counters: which tool, and is it worth it at current scale? | Open |
| 20 | Background work as an aggregate | Project K, newer services: a job row with status, progress and callback, polled by a hosted worker; older services: a job, schedule and job-manager abstraction. | Queued Laravel Jobs plus a per-article last-operation state readable through a reader (verified). | Is queryable job status a per-module concern (as Articles does) or one shared processing-state aggregate? | Open |
| 21 | Feature flags | Project K: the feature toggle is a domain aggregate with repository, cache decorator, service and admin controller, not a config file. | None found. | Runtime-togglable flags with an admin surface (Filament exists), or `config()` requiring a deploy? | Open |
| 22 | External integrations as isolated modules | Project B: one integration project per external system, each with its own registration and a fake sibling implementation for local development. | PDF export behind `PdfServiceProvider`; other outbound calls not surveyed. | When the first real external API arrives, is it a module with a fake sibling by rule? | Open |
| 23 | Migrations and schema delivery | Project K: migration names carry the ticket key; a separate migrator application with upgrade and report modes plus a data seeder. | `php artisan migrate` in the app container at deploy (inferred from CI notes in `AGENTS.md`); migration names are descriptive only. | Ticket-prefixed migration names, and a dry-run or report step before deploy? | Open |
| 24 | Build-level governance | Project K: a solution-wide build properties file pins the framework and turns analyzers on in CI only, opt-in locally. | Pint pinned in `composer.json` and enforced on changed files in CI; Larastan configured via `phpstan.neon`; `format-changed` scripts for local use (verified). | Is PHPStan level enforced in CI at the same strictness as locally, and is the legacy style drift ever paid down? | Open |
| 25 | Architecture and integration testing | Architecture tests not found in Project B; integration tests use a web application factory against real infrastructure, no container-per-test tooling. | `DomainLayerDependencyTest`, reader contract tests and a database query budget test for Articles (verified: `processor-api/tests/Integration/Infrastructure/Persistence/Readers/`). | Which of the Articles test kinds (contract, query budget, architecture) become the rule for every module with a Reader? | Open |
| 26 | Tenancy and language-region scoping | Project K scopes by language region and business unit rather than a tenancy abstraction. | Single tenant, single language. | Not applicable today; keep as a pointer in case localisation scoping ever appears. | Not applicable (single tenant) |

## Not present in either reference solution

Recorded so nobody goes looking twice: domain events or an outbox, a Unit of Work abstraction, the Specification pattern as objects, MediatR or CQRS, API versioning, a mapping library, container-per-test tooling, idempotency keys, optimistic concurrency tokens.

## Related

- [Application boundaries](./application-boundaries.md) — the layer table this backlog refines
- [`processor-api/AGENTS.md`](../../processor-api/AGENTS.md) — where decided rules land
- [`CONTEXT.md`](../../CONTEXT.md) — the language decisions here must respect
