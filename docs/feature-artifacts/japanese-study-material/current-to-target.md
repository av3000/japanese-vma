# Japanese Study Material — Current to Target

> **Status:** Migration map; each resource keeps its own contract
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Implementers, data maintainers, planners, and reviewers

## Resource Position

| Resource | Current | Target | Completion signal |
|---|---|---|---|
| Kanji | Public v1 list/detail, focused backend tests, and active frontend migration work. | Typed query/detail modules with correct generated schema and explicit aggregate choice. | No raw legacy kanji call on active routes; route tests cover filters and detail. |
| Radicals | Public v1 list/detail and backend tests; frontend remains less migrated. | React Query list/detail using generated clients and shared catalogue widget boundary. | Legacy radical calls and class patterns are absent from active routes. |
| Words | Public v1 list/detail, viewer catalogue-state include, and backend tests; some legacy detail code remains. | Generated list/detail clients with server-owned viewer state and typed mappings. | No legacy word detail call; list/detail/cache tests pass. |
| Sentences | Public v1 list/detail and backend tests; write/comment flows remain legacy. | v1 read/detail first, then separately approved write/comment contracts. | Active reads use v1; writes have explicit authorization and focused tests. |

## Cross-Resource Target Flow

```text
route search/identifier mapping
  -> resource-specific query hook
  -> generated v1 client
  -> resource-specific backend request/service/repository/resource
```

Catalogue membership remains a shared adjacent boundary rather than being reimplemented in every resource module.

## Migration Constraints

- Fix OpenAPI response shape before frontend type coercion.
- Preserve exact resource filters and pagination behavior during migration.
- Decide detail aggregation per resource; do not assume every relationship belongs in the base response.
- Coerce numeric route IDs once and reject invalid values before writes.
- Migrate shared catalogue behavior consistently across kanji, radical, word, and sentence details.
- Keep article extraction work separate from public Japanese-resource route migration.

## Explicit Non-Goals

- One generic query model for all four resources.
- A new import pipeline as part of frontend route migration.
- A broad cache/index project without measured pressure.
- Hand-edited generated client types.

## Evidence

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `client/AGENTS.md`
- `processor-api/tests/Feature/JapaneseMaterial/`
- `client/src/routes/japanese/`
