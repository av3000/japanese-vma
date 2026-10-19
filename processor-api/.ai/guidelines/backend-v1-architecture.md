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
