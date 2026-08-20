# Articles — Current to Target

> **Status:** Migration map; target work is not presented as complete
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Implementers, planners, and reviewers

## Flow Comparison

| Concern | Current | Target | Completion signal |
|---|---|---|---|
| List/detail reads | v1 backend and React Query frontend precedents are established. | Keep typed generated transport and focused mapping/query boundaries. | No legacy list/detail call remains on active article routes. |
| Create/update/delete | v1 routes, services, policies, resources, and tests exist. | Keep controllers thin and side effects in application modules. | Feature-flow review finds no duplicated legacy orchestration. |
| Moderation | Legacy helpers remain; v1 routes carry incomplete markers. | Complete v1 pending/status contracts with admin authorization and tests. | Generated clients replace legacy calls and all failure cases are tested. |
| Related study data | Article details and processing expose related words/kanji. | Keep include behavior explicit and schema-backed. | Runtime payload, OpenAPI, generated type, and tests agree. |
| Processing | Jobs and last-operation state exist. | Make failure/retry/latency observability operationally explicit. | Worker and UI behavior are verified for success and failure. |
| PDF | v1 kanji/word export is implemented. | Keep renderer and authorization behind application interfaces. | Generated clients and focused tests cover supported export kinds. |
| Frontend data access | Major paths are modern; small legacy helpers remain. | Route -> feature/query module -> generated client. | Raw article endpoint strings are absent from active routes. |

## Migration Constraints

- Preserve public/private visibility semantics.
- Preserve asynchronous processing rather than blocking article writes.
- Do not conflate publicity with moderation status.
- Regenerate OpenAPI before Orval when article response or request shapes change.
- Keep article extraction work separate from unrelated Japanese list/detail migrations.
- Retire legacy article routes only after an active-caller search and compatibility decision.

## Leading Follow-Up

Complete the administrative pending/status backend contract before migrating the dashboard and article review callers. The existing migration backlog describes this as a backend slice followed by a frontend slice rather than one broad rewrite.

## Evidence

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `client/AGENTS.md`
- `processor-api/AGENTS.md`
- `processor-api/tests/Feature/Articles/`
