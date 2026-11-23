# Deployment and Runtime

> **Status:** Baseline; repository configuration verified, provider state unverified
> **Last reviewed:** 2026-11-23
> **Evidence baseline:** Repository working tree and `.github/workflows/backend-ci.yml` inspected on 2026-11-23 (#708); provider dashboards not inspected
> **Audience:** Engineers, operators, incident responders, and reviewers

## Runtime Topology

The production topology is intentionally cross-system:

| Concern | Configured owner |
|---|---|
| Frontend verification and deploy | GitHub Actions |
| Backend image build and orchestration | GitHub Actions (`backend-ci.yml`), image in GHCR |
| One-off production tasks (migrate, data import, Passport client) | GitHub Actions (`backend-operations.yml`), `production` environment |
| Backend web service | Render, pulling `ghcr.io/av3000/japanese-vma-processor-api` |
| Real-time transport (Laravel Reverb) | Render, always-on web service `japanese-vma-reverb` running the backend image with `php artisan reverb:start --host=0.0.0.0 --port=$PORT` (ADR 0002) |
| Queue worker | Docker Compose (`processor-api/.deploy/worker/`); no production host deployed yet (#709) |
| Shared Redis queue/cache coordination | Upstash Redis |
| Primary application persistence | PostgreSQL database configuration |

This review verified repository configuration and contributor guidance. It did not inspect provider dashboards or make live requests.

**The Reverb row is a target, not a verified deployment.** The application code and the channel
authorisation are in the repository, but the
Render service and the platform variables it needs have not been created yet (issue #262). Until
they are, every client falls back to polling.

## Deployment Flow

```mermaid
sequenceDiagram
    participant Git as Source repository
    participant GHA as GitHub Actions
    participant Registry as GHCR
    participant Render as Render web service
    participant Reverb as Render Reverb service
    participant Redis as Upstash Redis

    Git->>GHA: push to master (release)
    GHA->>GHA: build frontend image, smoke test
    GHA->>Registry: push japanese-vma-client
    GHA->>Render: trigger frontend deploy

    GHA->>GHA: build backend image, smoke test
    GHA->>Registry: push japanese-vma-processor-api (sha-<sha>, latest)
    GHA->>GHA: migrate (only when migrations change, after approval)
    GHA->>Render: trigger backend web deploy
    Render->>Registry: pull latest image
    GHA->>Render: verify health, root page, kanji data
    Render->>Redis: use shared cache/queue configuration
    Render-->>Reverb: broadcast pending status (web)
    Reverb-->>Browser: private channel events
```

### Real-time transport

Processing status reaches the browser over Laravel Reverb (ADR 0002). Producers are the queue worker (every status transition) and the web service (`pending`, written in the create/update transaction). The browser subscribes to `private-processing_states.{article uuid}` on the detail page and to `private-App.User.{user uuid}` on the owner dashboard; public lists poll. Polling remains the correctness baseline, so a Reverb outage degrades to the 5 s / 15 s polling cadence rather than breaking the UI.

Configuration lives in platform variables, never in the repository:

| Where | Variables |
|---|---|
| Render Reverb service | `APP_KEY`, `APP_ENV=production`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_SERVER_HOST=0.0.0.0`, `REVERB_SERVER_PORT=$PORT`, `REVERB_ALLOWED_ORIGINS=<frontend origin>`, `REVERB_SCALING_ENABLED=false` |
| Render API web service | `BROADCAST_DRIVER=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST=<reverb hostname>`, `REVERB_PORT=443`, `REVERB_SCHEME=https`, and `QUEUE_CONNECTION=redis` |
| Worker host (#709) | the same `REVERB_*` values |
| GitHub repository variables (frontend) | `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT=443`, `VITE_REVERB_SCHEME=https`; the workflow fails when the first two are missing |

In production `allowed_origins` defaults to an empty list, so a Reverb service without `REVERB_ALLOWED_ORIGINS` refuses every browser rather than admitting all of them.

## Deployment Ordering

The backend release runs: image build and smoke test, push to GHCR, migrations (only when the release changes migrations, seeders or the permission catalogue, and only after a reviewer of the `production` environment approves), the Render web deploy, then verification. A failed or rejected migration stops the deploy, so new web code never runs against an unmigrated schema.

No production worker is deployed yet, so nothing consumes the production queue. When it is (#709), the worker must be deployed and verified before the web deploy, so new web code never enqueues jobs that no compatible worker can run.

Any production change involving jobs, serialization, queue names, Redis behavior, or environment variables must be reviewed across:

- application configuration;
- the worker Compose/runtime definition;
- the GitHub Actions release and operations workflows;
- Render web configuration;
- operational documentation.

## Local Runtime

Backend Compose commands run from `processor-api/`; frontend Compose commands run from `client/`. There is no assumed repository-root Compose file.

The backend Compose topology provides the application/web services plus dedicated database and test-runner services. Backend database tests must use the isolated `db-test` and `test-runner` lane rather than host PHP, the development database, or SQLite substitutes.

## Retained Operational Endpoints

Session authentication is served only by `api/v1/*` (`register`, `login`, `logout`, `me`). The
unversioned legacy session routes were retired in RET-AUTH-01, and `api/testing` went with them — it
pointed at a controller method that never existed and answered 500 on every request.

Two endpoints remain registered under the unversioned `api` prefix. Neither is a session endpoint,
neither has a v1 successor, and both are retained deliberately rather than pending migration:

| Endpoint | Defined in | Role | Owner | Revisit when |
| --- | --- | --- | --- | --- |
| `GET api/health` | `processor-api/routes/api.php` | Container and orchestrator liveness probe. Consumed by the Compose healthchecks and the Render runtime, not by the frontend. | Deployment/runtime | The hosting platform stops polling it, or a versioned health contract is introduced for it. Changing its path or payload is a deploy-configuration change, not an API change. |
| `POST api/broadcasting/auth` | `processor-api/app/Providers/BroadcastServiceProvider.php` | Laravel Echo private-channel authorization for the `App.User.{id}` and `last_operations.{uuid}` channels in `routes/channels.php`. Authorizes channel subscriptions, not sessions. | Async processing/broadcasting | Broadcasting is removed, or the channel authorization moves behind a versioned route. Its `auth:api` middleware is shared with the rest of the API, so Passport guard changes must consider it. |

Because `Broadcast::routes()` registers its route from the service provider, edits to
`routes/api.php` cannot remove it — but they also cannot be relied on to reveal it. Both endpoints
are asserted registered by `processor-api/tests/Feature/Routes/LegacyAuthRouteRetirementTest.php`,
and the health payload is asserted by `processor-api/tests/Feature/OperationalRoutesTest.php`.

## Retained Compatibility Paths

Two v1 paths still accept the pre-migration numeric identifiers. They exist so bookmarks and
external links from before the UUID cut-over keep resolving; they are compatibility, not the
canonical identity, and new callers must use UUIDs.

| Path | Defined in | Role | Owner | Revisit when |
| --- | --- | --- | --- | --- |
| `GET api/v1/posts/{identifier}` with a positive integer | `processor-api/routes/api_v1.php`, `App\Http\v1\Community\Posts\Controllers\PostController@show` | Resolves a legacy numeric post id and answers with the canonical UUID payload; the React `/community/:post_id` route rewrites the URL on first render. Comment reads and every write are UUID-only. | Community | The removal condition in `docs/legacy-v1-migration/specs/community-post-reads-backend.md` §13 is already met (#136, #138, #142, #144, #146, #152 are closed). Retire it in its own slice with a redirect audit: RET-POST-02 (#325). |
| `GET api/v1/catalogues/legacy/{id}` and the React paths `/lists`, `/list/:catalogueId`, `/newlist`, `/list/edit/:catalogueId` | `processor-api/routes/api_v1.php`, `App\Http\v1\Catalogues\Controllers\CatalogueController@resolveLegacyId`, `client/src/routes/CatalogueLegacyRedirects` | Lean numeric-to-UUID resolver (no view recorded, private catalogues answer 404) plus the client-side redirect page that consumes it. | Catalogues | No numeric catalogue URL is observed in access logs for one release cycle, or bookmark compatibility for pre-UUID list links is declared unsupported. Retire resolver and redirect page together. |

## Verification Boundaries

Repository evidence supports these checks:

- frontend typecheck, tests, and build through commands in `client/package.json`;
- backend health and route inspection through the Laravel container;
- backend feature tests through the dedicated Docker test lane;
- backend image smoke test and live verification through `backend-ci.yml`;
- worker behaviour through backend operational command tests.

Provider-level verification remains an operational action. A documentation review cannot confirm:

- the latest workflow or pipeline succeeded;
- the Render service is serving the expected image;
- a worker is running the expected revision;
- Upstash connectivity or queue depth is healthy;
- rollback credentials and provider retention settings are valid.

## Rollback and Compatibility Risks

- Web and worker releases can disagree on queued payloads if serialization contracts change incompatibly.
- Removing legacy endpoints before callers are migrated creates immediate frontend failures.
- Publishing generated clients from stale OpenAPI can compile while misrepresenting runtime payloads.
- Redis/cache shape changes need a deliberate compatibility and invalidation plan.
- Database migrations must remain compatible with the deploy order and both running revisions during rollout.

## Operational Sources

- `AGENTS.md`
- `.github/workflows/frontend-ci.yml`
- `.github/workflows/backend-ci.yml`
- `.github/workflows/backend-operations.yml`
- `processor-api/docker-compose.yml`
- `client/docker-compose.yml`
- `processor-api/tests/Feature/OperationalRoutesTest.php`
- `processor-api/tests/Feature/Routes/LegacyAuthRouteRetirementTest.php`
- `processor-api/tests/Feature/Console/VerifyQueueWorkerCommandTest.php`
