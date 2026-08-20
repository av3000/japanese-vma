# Current and Target State

> **Status:** Baseline migration map; target work is not presented as complete
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Maintainers, planners, reviewers, and AI-assisted contributors

## Purpose

This document prevents two common errors: treating legacy code as the preferred pattern because it exists, and treating an approved migration direction as implemented because it is documented.

## Comparison

| Concern | Current state | Target state | Completion signal |
|---|---|---|---|
| Backend routing | `api.php` and `api_v1.php` coexist. | Active callers use documented v1 routes; legacy routes retire separately. | Caller inventory is empty and retirement tests pass. |
| Backend HTTP layer | v1 modules use Requests/Resources/TypedResults; legacy controllers often own more behavior. | Controllers validate, map, delegate, and select responses only. | Feature-flow review finds no orchestration or persistence in touched controllers. |
| Application layer | Articles/catalogues have substantial services/actions; module depth varies. | Feature-local services/actions own authorization, transactions, enrichment, and side effects. | Every migrated flow has one clear use-case owner. |
| Domain layer | v1 modules contain DTOs, value objects, enums, models, and errors; legacy shapes remain. | Domain types remain independent of HTTP and Eloquent. | Static review finds no transport/persistence leakage in migrated domain code. |
| Persistence | Repository interfaces/adapters exist, with uneven legacy coverage. | Queries and Eloquent types stay behind infrastructure boundaries. | Application APIs return domain/result types rather than Eloquent/query builders. |
| Frontend routes | Modern function routes coexist with class components, raw `apiCall`, and transitional adapters. | Routes parse parameters, wire queries/mutations, gate state, and delegate rendering. | Touched routes contain no raw endpoint strings or page-owned server pagination. |
| Frontend server state | React Query is established for articles/catalogues and increasingly Japanese material. | Feature hooks/modules own stable keys, pagination, mapping, and invalidation. | Migrated surfaces have focused query tests and no duplicated cache policy. |
| API clients | Generated v1 clients coexist with legacy wrappers. | Use generated clients directly unless a wrapper adds real behavior or isolates unavoidable legacy transport. | Redundant renaming wrappers are removed; compatibility adapters name removal conditions. |
| Contract generation | Scramble/OpenAPI and Orval exist; schema inference can drift. | Backend source is corrected, OpenAPI regenerated/inspected, then Orval runs sequentially. | Contract-focused tests and generated types agree with intended runtime shape. |
| Authorization | v1 middleware and policies are established; legacy consistency varies. | Backend application/policy boundaries enforce every protected mutation and private read. | Success, unauthorized, forbidden, invalid, and not-found tests exist per migrated operation. |
| Background processing | Article extraction uses jobs and processing state; web/worker deploy separately. | Payloads are compatible, observable, retry-safe, and deploy-aware. | Worker verification and processing failure signals are operationally exercised. |
| Testing | Strong focused v1 backend tests and growing frontend tests coexist with legacy gaps. | Each migration slice includes contract/behavior tests at the owning boundary. | New behavior has focused tests and required broader checks report cleanly. |
| Deployment | Configuration spans GitHub Actions, GitLab CI, Render, GCP, and Upstash. | Cross-system changes update config, verification, rollout, and docs together. | Pipeline plus provider evidence identifies matching deployed revisions and healthy worker/web state. |
| Documentation | Rules, plans, and feature knowledge were previously distributed. | One indexed baseline owns architecture, evidence, feature behavior, and migration state. | Validator passes and owning documents change with material behavior. |

## Migration Order

The repository backlog establishes these principles:

1. Decide the backend contract before migrating a caller when the generated client is absent or wrong.
2. Correct backend schema generation before compensating in frontend types.
3. Migrate data access before redesigning feature UI.
4. Keep compatibility adapters narrow and name their removal condition.
5. Retire legacy routes only after active callers are proven gone.
6. Audit complete domain-feature flows before performing layer-wide cleanup.

## Core Feature Position

| Feature | Current strength | Leading migration gap |
|---|---|---|
| Articles | Strong v1 list/detail/write, processing, PDF, and frontend query precedents. | Administrative status/pending flows and remaining legacy helpers. |
| Catalogues | Strong v1 list/detail/write/item/PDF flows and modern frontend routes. | Legacy numeric list identity, saved-list compatibility, and remaining export parity. |
| Japanese study material | Public v1 list/detail endpoints and focused backend tests exist. | Frontend migration depth and detail aggregate/schema consistency vary by resource. |
| Community and engagement | Shared v1 comment creation, comment reads, and instance liking exist. | Posts and comment update/delete/moderation remain legacy-heavy. |

## Guardrails

- Preserve user-visible behavior unless a change is explicit and documented.
- Do not hand-edit `client/src/api/generated/`.
- Do not place HTTP or Eloquent types in domain code.
- Do not create generic managers or abstractions without a current reuse pressure.
- Do not replace a usable generated client with a parallel custom wrapper.
- Do not run DB-backed backend tests outside the dedicated Docker test lane.
- Do not describe registered but untested routes as completed capabilities.

## Explicit Non-Goals

- Microservices as the default next architecture.
- CQRS or event sourcing without a concrete read/write or audit-history pressure.
- Broad caching, indexing, or infrastructure work before module seams are clear.
- A single all-at-once legacy rewrite.
- A UI redesign bundled into contract migration.

## Related Documents

- [Architecture description](./architecture-description.md)
- [Application boundaries](../architecture/application-boundaries.md)
- [Legacy-to-v1 issue backlog](../legacy-v1-migration/backend-frontend-issue-backlog.md)
- [Project dashboard](./recaps/project-dashboard.md)
