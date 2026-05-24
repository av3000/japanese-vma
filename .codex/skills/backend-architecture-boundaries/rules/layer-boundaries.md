# Layer Boundaries

## Dependency Direction

Dependencies point inward.

`Infrastructure -> Application -> Domain`

Allowed:

- `app/Http/v1` depends on application services/actions, requests, resources, and `TypedResults`.
- `app/Application` depends on domain DTOs/models/value objects/errors and repository/service ports.
- `app/Infrastructure` depends on application ports and domain shapes to implement persistence, PDF, auth/session, or other adapters.
- Providers bind ports to adapters.

Forbidden unless explicitly accepted as transitional debt:

- Domain importing HTTP requests, responses, resources, framework facades, ORM models, query builders, or database concerns.
- Application/domain logic reaching directly into `app/Http/v1` resources.
- Controllers calling infrastructure repositories directly when a use case/application service should own orchestration.
- Infrastructure-specific return types becoming public domain contracts.

## Where Does This Code Go?

| Code | Location |
| --- | --- |
| HTTP validation, route param normalization, request contract | `app/Http/v1/**/Requests` |
| HTTP response shaping, API field names, envelope/resource contract | `app/Http/v1/**/Resources`, `App\Shared\Http\TypedResults` |
| One endpoint/use-case orchestration with policy, transaction, side effects | `app/Application/**/Actions` or feature service |
| Broader feature orchestration already established for the module | `app/Application/**/Services` |
| Pure invariant/value rule with no I/O | `app/Domain/**` model, value object, enum, or domain service |
| Persistence query, eager loading, batching, ORM interaction | `app/Infrastructure/Persistence/**` |
| Persistence-to-domain or domain-to-persistence shape conversion | mapper/assembler in infrastructure |
| External renderer, session, queue, storage, gateway implementation | infrastructure adapter |
| Interface for persistence/external dependency used by application | application port/interface |
| Interface binding | service provider/composition root |

## Keep It Simple Rule

Do not add CQRS, event sourcing, domain events, command buses, or new interface layers because a clean architecture article says so. Add them only for a current pressure: divergent read/write models, async boundary, multiple adapters, nontrivial side effects, test isolation, or repeated use-case duplication.
