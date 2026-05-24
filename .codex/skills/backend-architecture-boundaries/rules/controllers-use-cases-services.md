# Controllers, Use Cases, And Services

## Controller Rule

Controllers translate HTTP in/out and delegate orchestration.

Acceptable controller work:

- receive a validated request
- convert route/request values to DTOs or value objects
- call one clear application service/action method per use case
- translate `Result` failures to `TypedResults` or the local legacy response shape
- return resources/presenters or response factories

Controller risk:

- fetching multiple repositories or services to assemble one response
- enriching counts, hashtags, processing status, likes, downloads, comments, or ownership in the controller
- recording side effects such as views/downloads
- deciding business visibility or authorization inline
- doing manual response mapping that belongs in a resource or shaped DTO

## Application Use Case / Action / Service Rule

Application orchestration owns the use case: policy checks, transaction boundaries, side effects, repository calls, and assembly of a shaped output DTO/result.

Prefer:

- feature-local actions for focused side effects or reusable steps, such as download recording or batch stat loading
- feature services for established module orchestration, such as Article or Catalogue flows
- policies in application when authorization is part of the use case
- `Result` plus typed errors for service-level failures

Avoid:

- generic shared coordinators that collect unrelated responsibilities
- moving business rules into resources, mappers, or controllers
- turning every small CRUD method into a command bus/event pipeline

## Service SRP Rule

A service can orchestrate several collaborators for one use case. It becomes suspect when its reasons to change diverge.

Keeping authorization, a side effect, and persistence calls in one feature service can be acceptable when they are one use case and match nearby module style. Split into focused actions/adapters when the same step is reused, failure handling differs, the collaborator is an external mechanism, or the method starts changing for unrelated reasons.

Split when one class combines unrelated axes, for example:

- render PDF bytes
- authorize a viewer
- fetch feature metadata
- normalize domain data for a template
- record download analytics
- create HTTP response headers

In this repo, a better shape is usually feature export service -> PDF renderer port -> infrastructure renderer adapter -> record download action -> HTTP response factory.

## Transitional Code

Legacy/v1 coexistence is normal here. A controller or service may carry TODOs or legacy response behavior during migration. Treat it as real architecture debt only when it blocks the current change, leaks across layers, or creates contract drift.
