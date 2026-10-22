# AGENTS.md (processor-api)

This file defines **backend-specific** guidance for changes under `processor-api/`.

**Rules live in the `backend-standards` skill** (`.claude/skills/backend-standards/`): one file per rule ID (`ARCH-`, `CONTRACT-`, `RESULT-`, `DATA-`, `TEST-`), each with an impact level and an Incorrect/Correct example from the v1 modules. This file keeps direction, environment and process, and names the rule IDs that apply. Cite a rule by its ID.

## 1) Backend Architecture Direction

-   **Style:** Domain-driven modular monolith with layered responsibilities.
-   **Request flow and layers:** `ARCH-request-flow`.
-   **Primary direction:**
    -   Keep legacy behavior stable where needed.
    -   Prefer new/refactored work in v1 architecture/routes when practical: `ARCH-v1-routes`.

## 2) Route & Endpoint Policy

-   Rules: `ARCH-v1-routes`, `CONTRACT-route-specificity`, `CONTRACT-explicit-contract-changes`, `CONTRACT-comment-write-payload`.

## 3) Layering & Domain Boundaries

-   Rules: `ARCH-thin-controllers`, `ARCH-business-rules-in-application`, `ARCH-domain-dependency-direction`, `ARCH-domain-no-framework`, `ARCH-cross-module-service-interface`, `ARCH-focused-actions`, `ARCH-no-speculative-layers`, `ARCH-explicit-bindings`.
-   Persistence: `DATA-repositories-hide-persistence`, `DATA-list-readers`, `DATA-list-shape-naming`, `DATA-complete-domain-models`, `DATA-mappers-translate-shape`, `DATA-sql-in-infrastructure`.

## 4) Validation, DTO, and Identifier Conventions

-   Rules: `CONTRACT-request-per-endpoint`, `CONTRACT-partial-update-validation`, `CONTRACT-comment-write-payload`, `CONTRACT-clean-array-param-names`, `CONTRACT-v1-identifiers`.
-   DTOs and value objects sit in the request flow: `ARCH-request-flow`.

## 5) Response & Error Handling

-   Responses and errors: `RESULT-typed-results`, `RESULT-result-typed-errors`, `RESULT-envelope-matches-module`, `RESULT-no-mixed-envelopes`.
-   Schema and the generated client: `CONTRACT-named-resources`, `CONTRACT-resource-scalar-casts`, `CONTRACT-fix-schema-at-resource`, `CONTRACT-no-key-comments-in-toarray`, `CONTRACT-enum-schema-components`, `CONTRACT-resources-shape-only`, `CONTRACT-api-json-orval-workflow`, `TEST-openapi-schema-tests`.
-   Backend CI re-exports `api.json` (`composer openapi` with `APP_NAME=Laravel`, `APP_URL=http://localhost`) and fails when it differs from the committed file. Export with those two values or `info.title` and `servers[0].url` will differ.

## 6) Testing & Verification Expectations

-   **When backend code changes:** `TEST-feature-tests-per-endpoint`, run in the isolated lane per `TEST-docker-lane` (the rule holds the commands).
-   **Verification reporting:**
    -   List exact commands run and outcomes.
    -   Clearly report environment constraints when checks are blocked.
    -   Use `composer test:prepare` in the lane when you need an explicit schema reset before a run.
    -   More lane traps: `docs/agents/local-environment.md`.
    -   Local PHPUnit failures may also be followed by Telescope storage errors during teardown; treat those as secondary noise unless they are the first failing cause.
-   **Code style (Pint):**
    -   Run `./format-changed.ps1` (or `./format-changed.sh`) from `processor-api/` on the host before finalizing any PHP change. CI fails on style drift in changed files.
    -   From a worktree, pass the lane: `./format-changed.ps1 -Lane jvma<issue>` or `LANE=jvma<issue> ./format-changed.sh`. Without it the scripts refuse to run, because the default `laravel-app` container belongs to another checkout.
    -   Do not rely on `composer format`, `composer format:check`, or `vendor/bin/pint --dirty` inside the containers. Only `processor-api/` is bind-mounted while `.git` lives at the repository root, so `--dirty` matches nothing and reports a false `PASS 0 files`. This overrides the generic Pint guidance in the Laravel Boost block below.
    -   Do not run `composer format:all` or `composer quality:ci` on a feature branch; the repository still carries legacy style drift and a full pass creates a large unrelated diff.
    -   If the dedicated test lane itself is unavailable, report that environment limitation clearly and still run any unit or schema checks that remain feasible.

## 7) Refactor Guardrails

-   **Incremental over broad rewrites:**
    -   Keep changes focused and reviewable.
-   **No silent contract drift:**
    -   Call out payload/response/auth changes explicitly.
-   **Migration playbook:**
    -   Discovery first (legacy path, v1 counterpart, side effects), then layered implementation.

## 8) AI Guidance

-   Always-on Laravel conventions live in `processor-api/.ai/guidelines/`. Laravel Boost copies them into the generated block below, so edit the source files rather than the block.
-   Repo-wide skills live in the root `.claude/skills/`, mirrored to `.codex/skills/`. The agent workflow (issues, review, PRs, commit shape) is in the root `AGENTS.md` §7.
-   Citable backend rules are in the `backend-standards` skill; `review-branch` cites them by ID.
-   Keep this file as the source of truth for backend constraints. Rules here win over Boost-installed vendor skills (`laravel-best-practices`, `configuring-horizon`) and over the generic guidance in the generated block.

<laravel-boost-guidelines>
=== .ai/backend-response-and-errors rules ===

# Backend Response And Errors

Each rule below is a citable rule in the `backend-standards` skill (`.claude/skills/backend-standards/rules/<ID>.md`), with an Incorrect/Correct example.

For v1 endpoints:

- Success responses are a named Resource or an `App\Shared\Http\TypedResults` helper, never ad hoc JSON: `RESULT-typed-results`.
- Service-level failures are `App\Shared\Results\Result` with typed error classes: `RESULT-result-typed-errors`.
- Pagination, envelope shape and include flags follow the nearest v1 module: `RESULT-envelope-matches-module`.
- A Request class rejects invalid input before the service runs: `CONTRACT-request-per-endpoint`.

When changing contracts:

- Contract changes are explicit, and legacy behaviour stays unless a migration moves it to v1: `CONTRACT-explicit-contract-changes`.
- One endpoint family never mixes legacy JSON and `TypedResults`: `RESULT-no-mixed-envelopes`.

=== .ai/backend-v1-architecture rules ===

# Backend V1 Architecture

Each rule below is a citable rule in the `backend-standards` skill (`.claude/skills/backend-standards/rules/<ID>.md`), with an Incorrect/Correct example from the v1 modules. Open the rule file before applying it.

Request flow for v1 endpoints (`ARCH-request-flow`):

`routes/api_v1.php` -> v1 Controller -> Request -> DTO or Value Object -> Application Service or Policy -> Repository Interface -> Infrastructure Repository, Model, Mapper or Builder -> Resource -> `TypedResults`

By layer:

- `app/Domain`: domain models, DTOs, value objects, enums and typed errors.
  - Never imports `App\Application`, `App\Infrastructure` or `App\Http`: `ARCH-domain-dependency-direction`, enforced by `tests/Unit/Architecture/DomainLayerDependencyTest`.
  - No HTTP requests or responses, Eloquent models or query builders: `ARCH-domain-no-framework`.
  - List shapes are named `*QueryCriteria`, `*Includes`, `*PageDTO`, `*ListResultDTO` and `*SortCriteria`: `DATA-list-shape-naming`.
  - Domain models are constructed complete by their mapper; per-read enrichment lives on a `*ListItemDTO` (ADR 0001): `DATA-complete-domain-models`.
- `app/Application`: use cases, policies, actions, jobs and repository interfaces.
  - Business rules and ownership checks live here, not in controllers: `ARCH-business-rules-in-application`.
  - List reads with filters, facets or pagination go through a `*ReaderInterface`: `DATA-list-readers`.
  - Anything another module calls is a `*ServiceInterface` returning `Result`: `ARCH-cross-module-service-interface`.
  - A reusable or side-effectful step is a focused action: `ARCH-focused-actions`.
- `app/Infrastructure/Persistence`: persistence models, repositories, mappers and builders.
  - Repositories hide persistence and return domain shapes: `DATA-repositories-hide-persistence`.
  - Mappers translate shape and hold no policy: `DATA-mappers-translate-shape`.
  - The backend targets PostgreSQL only; SQL lives here, never in Domain, Application or Http: `DATA-sql-in-infrastructure`.
- `app/Http/v1`: controllers, requests and resources.
  - Controllers coordinate and return a Resource or `TypedResults`: `ARCH-thin-controllers`.

Guardrails: reuse nearby v1 patterns before adding layers (`ARCH-no-speculative-layers`), and keep interface bindings explicit in the providers (`ARCH-explicit-bindings`).

=== .ai/repository rules ===

# Repository Guidance

Use this repository as a multi-application workspace:

- `processor-api/` contains the Laravel backend.
- `client/` contains the React frontend.
- `docs/` contains project documentation assets.

When migrating backend endpoints:

- Preserve legacy behavior intentionally; contract changes follow `CONTRACT-explicit-contract-changes` in the `backend-standards` skill.
- Keep diffs focused. Do not mix endpoint migration work with unrelated refactors.
- Treat `processor-api/AGENTS.md` as the backend source of truth for implementation constraints.
- Use `.ai/guidelines/` for always-on conventions. Task-specific skills live in the repo-root `.claude/skills/`, mirrored to `.codex/skills/`.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- filament/filament (FILAMENT) - v5
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/passport (PASSPORT) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- react (REACT) - v16

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `configuring-horizon` — Use this skill whenever the user mentions Horizon by name in a Laravel context. Covers the full Horizon lifecycle: installing Horizon (horizon:install, Sail setup), configuring config/horizon.php (supervisor blocks, queue assignments, balancing strategies, minProcesses/maxProcesses), fixing the dashboard (authorization via Gate::define viewHorizon, blank metrics, horizon:snapshot scheduling), and troubleshooting production issues (worker crashes, timeout chain ordering, LongWaitDetected notifications, waits config). Also covers job tagging and silencing. Do not use for generic Laravel queues without Horizon, SQS or database drivers, standalone Redis setup, Linux supervisord, Telescope, or job batching.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. In this repository, never use `php artisan test`: run tests in the isolated Docker test lane from `processor-api/`, `docker compose -f docker-compose.test.yml -p jvma<issue> exec -T test-runner composer test -- ...`, with a specific filename or filter. Lane setup is in `processor-api/AGENTS.md` §6; the rules are `TEST-docker-lane` and `TEST-feature-tests-per-endpoint` in the `backend-standards` skill.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- Run tests in the isolated Docker test lane from `processor-api/`, never with `php artisan test`. `jvma<issue>` is a project name unique to the checkout (`TEST-docker-lane`).
- To run all tests: `docker compose -f docker-compose.test.yml -p jvma<issue> exec -T test-runner composer test`.
- To run all tests in a file: `docker compose -f docker-compose.test.yml -p jvma<issue> exec -T test-runner composer test -- tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `docker compose -f docker-compose.test.yml -p jvma<issue> exec -T test-runner composer test -- --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
