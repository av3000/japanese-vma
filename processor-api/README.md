# Japanese VMA Processor API

`processor-api/` contains the Laravel backend for Japanese VMA. It serves HTTP APIs, background job processing, realtime services, and the persistence layer used by the frontend and supporting workflows.

## Stack

-   PHP 8.3
-   Laravel 12
-   Laravel Passport
-   Laravel Horizon
-   Laravel Reverb
-   Laravel Telescope
-   Laravel Boost
-   Laravel PHPStan(https://larastan.org/)
-   Laravel Pint
-   Dedoc Scramble
-   Sentry Laravel
-   Spatie Laravel Permission
-   Barryvdh Laravel Snappy
-   MySQL for local development
-   Redis for queue and cache coordination

## Backend Architecture

The backend is a modular monolith with a layered direction, not a fully completed clean-architecture rewrite.

### Main layers

-   `app/Http/` and `app/Http/v1/`
    -   controllers, form requests, middleware, and API resources
-   `app/Domain/`
    -   domain models, DTOs, value objects, enums, factories, errors, and query criteria
-   `app/Application/`
    -   services, actions, policies, jobs, and repository interfaces
-   `app/Infrastructure/Persistence/`
    -   Eloquent-backed repositories, mappers, and persistence models
-   `app/Providers/`
    -   service and repository bindings for the application container

### Typical request flow

For newer `v1` endpoints, the current request flow is generally:

1. Route entry in `routes/api_v1.php`
2. Controller in `app/Http/v1/.../Controllers`
3. Form request validation
4. DTO or value-object construction
5. Application service and composed actions
6. Repository interface call into persistence implementation
7. Resource or typed result returned as the HTTP response

This flow exists alongside legacy routes and controllers that still live in `routes/api.php` and older Laravel-style controller code. Contributor docs should treat both as real until migration is finished.

### Current patterns in use

-   DTOs and value objects are used heavily in newer modules.
-   Repository interfaces are bound in providers and implemented in `Infrastructure/Persistence/Repositories`.
-   Services coordinate business workflows and authorization checks.
-   Eloquent is still the underlying persistence mechanism.
-   Queue-backed jobs are used for longer-running processing, including article kanji processing.

## Routing And API Surface

-   `routes/api_v1.php` contains the newer versioned API surface under `/api/v1`.
-   `routes/api.php` still contains legacy endpoints and the `/api/health` endpoint.
-   Scramble generates API docs for the `api/v1` path at `http://localhost:8080/docs/api`.

## Local Setup

The backend should be run through Docker so the PHP extensions and runtime match the application requirements.

1. Create `processor-api/.env` from `processor-api/.env.example`.
2. Start the local containers.
3. Install Composer dependencies inside the Laravel container.
4. Generate the app key.
5. Run the standard migrations and the Japanese data migrations.
6. Install Passport keys and clients.
7. Seed the database.

```bash
cd processor-api
docker compose up -d --build
docker compose exec laravel-app composer install
docker compose exec laravel-app php artisan key:generate
docker compose exec laravel-app php artisan migrate
docker compose exec laravel-app php artisan migrate --path=database/migrations/japanese-data
docker compose exec laravel-app php artisan passport:install
docker compose exec laravel-app php artisan db:seed
```

If configuration or autoload state gets stale during local work:

```bash
docker compose exec laravel-app composer dump-autoload
docker compose exec laravel-app php artisan config:clear
docker compose exec laravel-app php artisan cache:clear
```

PDF generation uses Laravel Snappy. If PDF features fail locally, verify the wkhtmltopdf binary paths in `.env` and `config/snappy.php`.

Useful local URLs:

-   App/API: `http://localhost:8080`
-   Health check: `http://localhost:8080/api/health`
-   Scramble docs: `http://localhost:8080/docs/api`
-   Telescope: `http://localhost:8080/telescope`

## Local Runtime Services

The local Docker Compose setup includes:

-   `laravel-app` for PHP and Artisan/Composer commands
-   `webserver` for the HTTP entrypoint on port `8080`
-   `db` for MySQL
-   `redis` for local Redis-backed behavior
-   `reverb` for websocket/realtime support
-   `queue` for local queue worker execution
-   `redisinsight` for Redis inspection

## Recommended Commands

```bash
cd processor-api
docker compose up -d --build
composer format
composer stan
composer test
composer quality
composer quality:ci
php artisan route:list
php artisan horizon
docker compose logs -f queue
docker compose logs -f webserver
```

Use `composer format` during local work to run Pint against dirty PHP files when Git metadata is available to the PHP runtime.

Use `composer test` for the PHPUnit suite, and `composer quality` before handing off a backend change.
In CI or review gates, prefer `composer quality:ci` so Pint checks formatting without modifying files.

### Larastan - static analysis

Use `composer stan` for Larastan(Laravel phpstan static ),
looks for type errors, missing classes, bad method calls, impossible conditions, wrong array shapes, nullable mistakes, and similar problems.

`phpstan.neon` has levels of strictness and other configurations.
`phpstan-baseline.neon` is the current debt list, and is included in phpstan to avoid reruning known files issues. After making fixes from debt list, do not manually edit it, use `composer stan --generate-baseline`.

```bash
composer stan -- app/Application/Articles/Services/ArticleKanjiProcessingService.php
composer stan -- app/Application/Articles
composer stan -- app/Foo.php app/Bar.php
```

### Pint - code style fixer

The current Docker Compose mount exposes `processor-api/` to the Laravel container, while the `.git` directory lives at the repository root. Because of that, Pint's `--dirty` mode may report `0 files` inside the container. For targeted Docker formatting, run Pint against explicit files or directories:

```bash
vendor/bin/pint app/Path/To/File.php
vendor/bin/pint --test app/Path/To/File.php
```

If the branch is still carrying legacy style drift, format touched PHP files directly during daily work and reserve whole-repository checks such as `composer format:check` or `composer quality:ci` for cleanup branches or CI gates with an agreed formatting baseline.

Larastan uses `phpstan-baseline.neon` to ignore the current backlog of existing findings. When fixing static-analysis issues, regenerate the baseline only after confirming the reduction is intentional.

research what do you know about @property-read

## Laravel Boost

Laravel Boost is installed for AI-assisted Laravel development. Run Boost commands inside the Laravel container:

```bash
php artisan list boost
php artisan boost:install
php artisan boost:mcp
php artisan boost:update
```

-   `boost:install` installs Boost support files. Use `--ignore-guidelines` or `--ignore-mcp` when only one part should be skipped.
-   `boost:mcp` starts the Boost MCP server; this is usually called from `mcp.json` rather than run manually.
-   `boost:update` refreshes the Laravel Boost guidelines to the latest installed guidance.

## Deployment Summary

Production behavior spans more than just Render:

-   `.gitlab-ci.yml` builds and verifies the backend image
-   the worker runtime is deployed separately to a GCP VM over SSH
-   Render serves the live backend web application
-   Upstash Redis is used for live queue and cache coordination

When changing backend infrastructure, queue behavior, or environment-sensitive code, check the CI pipeline, worker runtime, and Redis assumptions together.

## Other notes

-   Preserve legacy behavior intentionally; do not silently rewrite contracts.
-   Run Composer and Artisan inside the `laravel-app` container instead of a local Windows PHP install.
-   Reuse the existing service, repository, DTO, and resource patterns in the touched module before introducing new abstractions.
