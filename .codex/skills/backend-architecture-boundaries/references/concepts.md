# Concepts

These definitions are architecture terms adapted to this repo. They are not Laravel-specific.

| Term | Definition |
| --- | --- |
| Controller | Transport adapter that receives HTTP input, delegates to application code, and returns an HTTP response/resource. |
| Request / validation object | Edge contract that validates and normalizes incoming input before use-case orchestration. |
| Application use case / action / application service | Orchestrates one business operation: policies, transactions, ports, side effects, and shaped result/error output. |
| Domain model / entity / value object | Core business object or immutable value that expresses domain invariants without framework, persistence, or transport dependencies. |
| Domain service | Pure domain logic that does not fit a single entity/value object and has no I/O dependency. |
| Repository interface / port | Inward-facing contract used by application code to access persistence or read models without knowing storage details. |
| Repository implementation / adapter | Infrastructure class that implements a repository port using ORM, queries, storage, or external systems. |
| Mapper / assembler | Shape translator between persistence/domain/application/API boundaries. It should not own business policy. |
| DTO | Explicit data shape for input/output across use-case, domain, or response seams. |
| API resource / response presenter | Edge object that serializes application/domain output into the public API contract. |
| Infrastructure adapter | Outer-layer implementation for persistence, rendering, auth session, external service, queue, cache, storage, or similar I/O. |
| Composition root / DI binding | Provider/bootstrap location that wires ports to adapters and constructs the runtime graph. |

## Pattern Boundaries

| Pattern | Use it for | Do not treat it as |
| --- | --- | --- |
| Clean architecture | Dependency direction and use-case boundaries | A mandatory four-folder template |
| Hexagonal architecture / ports and adapters | Keeping application logic independent from I/O mechanisms | A reason to wrap every class in an interface |
| DDD tactical patterns | Modeling meaningful domain language, entities, value objects, invariants | A folder rename exercise |
| CQRS | Divergent read/write models or workloads | The default shape for CRUD |
| Events/event sourcing | Async consistency or reconstructing state from event history | A default persistence model |
