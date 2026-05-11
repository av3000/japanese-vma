# Backend V1 Architecture

Use this request flow for v1 Laravel endpoints:

`routes/api_v1.php` -> v1 Controller -> Request -> DTO or Value Object -> Application Service or Policy -> Repository Interface -> Infrastructure Repository, Model, Mapper or Builder -> Resource -> `TypedResults`

Keep responsibilities separated by layer:

- `app/Domain`
  - Put domain models, DTOs, value objects, enums, and typed errors here.
  - Do not leak HTTP requests, responses, Eloquent models, or raw query builders into this layer.
- `app/Application`
  - Put use-case orchestration, policies, actions, jobs, and repository interfaces here.
  - Keep business rules here instead of controllers.
- `app/Infrastructure/Persistence`
  - Put persistence models, repositories, and mappers or builders here.
  - Encapsulate query logic and batch loading here instead of the HTTP layer.
- `app/Http/v1`
  - Put controllers, requests, and resources here.
  - Keep controllers thin and focused on coordination, mapping, and returning `TypedResults`.

Apply these guardrails:

- Reuse nearby v1 patterns before inventing new abstractions.
- Add a mapper or builder when the persistence shape should not leak into the domain shape.
- Add a focused service or action when logic is reusable, side-effectful, batch-heavy, or heterogeneous.
- Keep interface bindings explicit in service and repository providers.
