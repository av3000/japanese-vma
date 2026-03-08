# Migration Checklist

Use this checklist before closing a legacy-to-v1 migration.

## Discovery

- Identify the legacy route entry point.
- Inspect controller methods, helpers, legacy models, and raw queries.
- Record request params, auth rules, response shape, sorting, pagination, and side effects.

## Contract

- Define the v1 route and identifier shape.
- Define query parameters, include flags, pagination rules, and error behavior.
- Call out any intentional contract drift.

## Layering

- Add or update domain DTOs, models, enums, value objects, and errors.
- Add application services, policies, actions, and repository interfaces.
- Add infrastructure repositories, mappers, builders, and batch queries.
- Add HTTP requests, controllers, resources, and route entries.
- Register provider bindings.

## Verification

- Verify `TypedResults` response shape.
- Verify auth or visibility rules.
- Verify search, sort, and pagination behavior.
- Verify side effects such as view tracking, jobs, or grouped counters.
- Add feature tests for happy path and failure cases.
