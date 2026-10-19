# Backend V1 Architecture

Use this request flow for v1 Laravel endpoints:

`routes/api_v1.php` -> v1 Controller -> Request -> DTO or Value Object -> Application Service or Policy -> Repository Interface -> Infrastructure Repository, Model, Mapper or Builder -> Resource -> `TypedResults`

Keep responsibilities separated by layer:

- `app/Domain`
  - Put domain models, DTOs, value objects, enums, and typed errors here.
  - Do not leak HTTP requests, responses, Eloquent models, or raw query builders into this layer.
  - Never import `App\Application`, `App\Infrastructure` or `App\Http` here; `tests/Unit/Architecture/DomainLayerDependencyTest` fails the build if you do.
  - Name shapes after their siblings: `*QueryCriteria` for list input (`Domain/{Module}/Queries`), `*Includes` for optional-enrichment flags, `*PageDTO` for raw reader output, `*ListResultDTO` for the enriched envelope, `*SortCriteria` for sort. Do not introduce `*Query`, `*Projection` or `*Page` for these roles.
  - Domain models are constructed complete by their mapper, from their own row. Per-read enrichment - counts, previews, page totals - belongs on a `*ListItemDTO` beside the model, never as defaulted constructor arguments filled in later by a `with*()` wither. A model that can be handed out half-populated gives every reader a value it cannot tell apart from the truth; see `docs/adr/0001-read-enrichment-lives-beside-the-entity.md`.
- `app/Application`
  - Put use-case orchestration, policies, actions, jobs, and repository interfaces here.
  - Keep business rules here instead of controllers.
  - List reads with filters, facets or pagination go through a `*ReaderInterface` in `Application/{Module}/Interfaces/Readers`; identity reads and writes stay on the repository. A reader returns Domain models and scalar pagination, never a paginator. Reference: `Articles/Interfaces/Readers/ArticleListReaderInterface`.
  - Anything another module calls is a `*ServiceInterface` returning `Result`. Actions are module-internal steps and are not injected across modules.
- `app/Infrastructure/Persistence`
  - Put persistence models, repositories, and mappers or builders here.
  - Encapsulate query logic and batch loading here instead of the HTTP layer.
  - The backend targets PostgreSQL only (CI, compose and production). PostgreSQL-specific SQL (`ILIKE`, `FILTER (WHERE ...)`, partial indexes) is allowed here. Domain and Application code contain no SQL at all.
- `app/Http/v1`
  - Put controllers, requests, and resources here.
  - Keep controllers thin and focused on coordination, mapping, and returning `TypedResults`.

Apply these guardrails:

- Reuse nearby v1 patterns before inventing new abstractions.
- Add a mapper or builder when the persistence shape should not leak into the domain shape.
- Add a focused service or action when logic is reusable, side-effectful, batch-heavy, or heterogeneous.
- Keep interface bindings explicit in service and repository providers.
