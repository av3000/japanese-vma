---
name: legacy-to-v1-migration
description: Migrate legacy Laravel endpoints in this repository from `processor-api/routes/api.php` and legacy controllers into the v1 clean architecture in `processor-api/routes/api_v1.php`, `app/Http/v1`, `app/Domain`, `app/Application`, and `app/Infrastructure/Persistence`. Use when refactoring controller-heavy legacy logic into request validation, DTOs, domain models, repository interfaces, repositories, policies, resources, typed errors, and `TypedResults`, while preserving behavior unless an intentional contract change is requested.
---

# Legacy To V1 Migration

## Overview

Use this skill to migrate legacy backend endpoints into the repository's v1 Laravel architecture without dragging legacy coupling into new modules. Prefer existing local v1 patterns first, then add new abstractions only when they reduce coupling, clarify responsibilities, or prevent repeated query logic.

## Workflow

1. Inventory the legacy endpoint.
- Read the legacy route entry in `processor-api/routes/api.php`.
- Read the controller method and any helpers, legacy models, raw queries, and side effects it depends on.
- Capture request params, auth rules, visibility rules, sorting, pagination, response shape, and write side effects before changing anything.

2. Anchor on a nearby v1 module.
- Inspect the closest existing v1 module and mirror its conventions before inventing new structure.
- Use Articles and Catalogues as primary pattern anchors for list, show, resources, `TypedResults`, services, and repository boundaries.

3. Define the v1 contract first.
- Specify the route in `processor-api/routes/api_v1.php`.
- Specify identifiers, query parameters, include flags, response envelope, pagination shape, and error behavior.
- Preserve legacy behavior unless the task explicitly calls for a v1 contract improvement.

4. Design by layer before writing code.
- `Domain`: models, DTOs, enums, value objects, errors, collection wrappers.
- `Application`: services, policies, actions or jobs, repository interfaces.
- `Infrastructure`: persistence repositories, models, mappers, builders, batched queries.
- `Http/v1`: requests, controllers, resources, `TypedResults`.

5. Implement from inside out.
- Create domain contracts first.
- Add application orchestration and repository interfaces next.
- Add infrastructure repositories and mappers.
- Finish with requests, controllers, resources, routes, and provider bindings.

6. Validate behavior, not just structure.
- Add feature tests for happy path, not found, auth or visibility failures, filters, sorting, pagination, and side effects.
- Report assumptions, intentional contract changes, and any environment limits that prevented validation.

## Required Patterns

- Use dedicated Request classes for endpoint validation.
- Use DTOs for endpoint inputs and operation criteria when there is non-trivial filtering, sorting, or include behavior.
- Use domain models and collection wrappers for paginated or aggregate results.
- Use repository interfaces in `app/Application` and implementations in `app/Infrastructure/Persistence`.
- Use resources for v1 response mapping and `TypedResults` for HTTP responses.
- Use typed error classes plus `Result` for service failures.
- Bind new interfaces explicitly in service and repository providers.

## Design Heuristics

- Add a mapper when Eloquent or query output should not leak into the domain shape.
- Add a factory when entity creation has invariants or multiple valid construction paths.
- Add a dedicated resolver or service when an endpoint aggregates multiple item types or needs batch enrichment.
- Add a focused action when side effects are standalone and reusable, such as tracking views or loading grouped stats.

## Anti-Patterns

- Do not keep business logic in controllers.
- Do not query the database directly in controllers or resources.
- Do not leak Eloquent models into domain contracts.
- Do not use untyped array contracts between layers where DTOs or domain models are justified.
- Do not introduce N+1 loading in the HTTP layer.
- Do not mix legacy JSON response style with v1 `TypedResults` style inside the same migrated endpoint family without intent.

## References

- Read `references/architecture-map.md` for repo-specific folder and binding expectations.
- Read `references/customlist-to-catalogue-case-study.md` before migrating aggregate or list-heavy endpoints.
- Read `references/migration-checklist.md` before finalizing implementation or review notes.
