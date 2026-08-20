# Architecture Description

> **Status:** Baseline; current, target, legacy, and open claims labelled
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Engineers, architects, reviewers, and AI-assisted contributors

## Overview

Japanese VMA is a monorepo containing a React single-page application and a Laravel API. It combines Japanese reference material with user-created articles, catalogues, community content, engagement, asynchronous language processing, and PDF output.

The backend is an incrementally modernized modular monolith. Legacy controllers/routes and v1 layered modules coexist. The target is not a service split: it is a robust monolith whose feature seams, dependency direction, contracts, and operational boundaries remain understandable as the codebase grows.

## Stakeholders and Concerns

| Stakeholder | Concerns |
|---|---|
| Learner | Fast discovery, correct Japanese data, stable saved state, accessible reading and study workflows. |
| Contributor | Reliable authoring, editing, processing feedback, publication state, and ownership enforcement. |
| Administrator | Secure user/role operations and moderation behavior. |
| Developer | Clear current/target boundaries, local feature ownership, generated contract accuracy, and focused tests. |
| Operator | Health checks, queue visibility, deploy ordering, web/worker compatibility, and recoverability. |

## Architecture Principles

1. **Module seams before infrastructure.** Improve development scalability before adding runtime machinery.
2. **Incremental migration.** Legacy and v1 paths may coexist; preserve compatibility until callers move.
3. **Schema source first.** Fix Requests, Resources, enums, and response annotations before regenerating clients.
4. **Thin transport boundaries.** Controllers/routes coordinate; application modules own use cases and side effects.
5. **Explicit evidence.** Configuration is not described as live verification, and planned work is not described as complete.
6. **Focused depth.** Add actions, services, policies, or adapters when they improve current orchestration—not to satisfy a folder pattern.

## Context View

The [system context](../architecture/system-context.md) identifies users, the React client, Laravel API, MySQL, Redis, queue worker, realtime channel, reference datasets, CI systems, and hosting boundaries.

## Functional View

### Content discovery

Public routes expose article and Japanese-material list/detail data. Catalogue list/detail reads apply visibility rules. The React route tree provides article, catalogue, kanji, radical, word, sentence, community, authentication, and dashboard surfaces.

### Content ownership

Authenticated users create and maintain articles and catalogues. Backend application policies enforce owner/admin rules; UI visibility alone is never the security boundary.

### Study organization

Catalogues organize typed study items. The current v1 API includes catalogue list/detail, for-item picker, add/remove item, update/delete, and supported PDF export routes. Legacy list identity and some export behavior remain compatibility debt.

### Language processing

Article writes can dispatch kanji and word processing jobs. Processing attaches extracted study data and exposes status to the frontend through last-operation/realtime boundaries.

### Community and engagement

Comments, likes, hashtags, views, and downloads can attach to multiple entity types. Article and catalogue comment reads have v1 endpoints; generic comment creation and instance liking have v1 writes. Posts and several update/delete comment flows remain legacy.

## Information View

The main domain concepts are:

- user, role, and permission;
- article and author;
- catalogue, catalogue type, and catalogue item;
- kanji, radical, word, and sentence;
- comment and parent comment;
- like, hashtag, view, and download;
- processing status and last operation;
- PDF document and export kind.

UUIDs are preferred at public v1 identity boundaries where established. Some shared engagement writes also require numeric entity IDs because the current polymorphic persistence model uses them. The object-template enum bridges domain entity categories to legacy numeric identifiers.

See [data and integrations](../architecture/data-and-integrations.md) and the four [feature packets](../feature-artifacts/) for behavioral detail.

## Development View

### Frontend

The target composition is:

```text
route -> feature component -> feature API/query hook -> generated client
```

React Query owns server state, cache keys, pagination, and invalidation where suitable. Orval-generated files remain transport outputs. Small feature adapters are justified when they add mapping/cache behavior or isolate a real legacy dependency.

### Backend

The target v1 dependency flow is:

```text
route -> controller -> request -> DTO/value object -> application use case
      -> repository interface -> infrastructure adapter -> resource -> TypedResults
```

The domain layer avoids HTTP and Eloquent types. Application modules own authorization, transactions, orchestration, and side effects. Infrastructure owns persistence. Resources map response shape.

The [application-boundaries view](../architecture/application-boundaries.md) includes representative flows and legacy contrasts.

## Concurrency and Background Work

Article kanji and word processing occurs through queued jobs. Web and worker runtimes are separately deployed in the configured production topology, so payload compatibility and deploy ordering matter.

Realtime processing-status updates can change client cache state after an initial HTTP response. Frontend consumers must treat job completion as asynchronous and keep cache updates scoped to stable query keys.

## Deployment View

Frontend delivery is configured through GitHub Actions. Backend delivery is configured through GitLab CI, which builds the image, deploys and verifies the GCP worker, and then triggers Render. Upstash Redis coordinates production queue/cache behavior according to repository guidance.

The [deployment view](../architecture/deployment-and-runtime.md) distinguishes source-verified configuration from provider state that this review did not inspect.

## Security View

### Verified controls

- Authenticated v1 routes use the API authentication middleware.
- Administrative v1 routes add an admin-role middleware boundary.
- Article and catalogue application policies enforce ownership/visibility decisions.
- Dedicated Request classes validate v1 payloads.
- Typed result/error boundaries avoid ad hoc success/error response shapes in migrated modules.

### Risks and target controls

- Legacy endpoints may enforce authorization inconsistently and require focused migration review.
- Generic entity writes require the entity type, numeric ID, and UUID to remain coherent.
- Browser/API deployment across origins requires deliberate CORS, credential, and cookie configuration.
- Provider credentials, rate limits, backups, and incident procedures are operational state and were not verified here.

## Operational View

The backend exposes operational routes/tests and a queue-worker verification command. Backend DB-backed tests use a dedicated Docker test lane. Frontend verification includes typecheck, tests, and build commands, with the known possibility that sandboxed Vite/Vitest startup can fail from environment-level process restrictions.

Operational readiness still requires provider checks for deployed revisions, worker health, queue depth, Redis availability, database backup/restore, and error monitoring.

## Architectural Decisions and Constraints

| Decision or constraint | State |
|---|---|
| Keep a modular monolith as the default target. | Target |
| Preserve legacy routes until callers are migrated. | Target and migration constraint |
| Prefer v1 routes and typed result/resource patterns for new work. | Target with current precedents |
| Prefer generated clients when the OpenAPI contract is usable. | Target with current precedents |
| Keep production worker and web deployment coordinated across systems. | Verified configuration |
| Do not adopt CQRS, event sourcing, or microservices without concrete pressure. | Explicit non-goal |

## Leading Risks

1. Legacy/v1 behavior can drift while both paths remain active.
2. Generated client types can misrepresent runtime payloads when schema inference is wrong or stale.
3. Web and worker revisions can disagree on queued payloads.
4. Frontend routes may mix modern query modules with raw legacy calls, increasing invalidation and error-state inconsistency.
5. Product rules embedded only in legacy controllers are easy to lose during migration.
6. Documentation can become falsely authoritative unless evidence dates and open runtime checks remain visible.

## Open Questions

- Which registered v1 administrative article routes have complete controller and test support?
- Which production provider revisions are currently deployed?
- Which Japanese detail aggregates should be lean versus include-driven?
- When should generic v1 comment update/delete contracts replace resource-specific legacy mutations?
- Which observability signals are required for article processing latency and queue failures?

See the [evidence manifest](./evidence-manifest.md) for resolution locations.
