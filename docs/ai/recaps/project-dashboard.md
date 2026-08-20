# Project Dashboard

> **Status:** Baseline snapshot; not a live operational dashboard
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Maintainers, technical leads, planners, and reviewers

## Snapshot

Japanese VMA has a credible v1 modular-monolith direction with strong article and catalogue slices, public v1 Japanese-resource reads, and shared engagement foundations. Its primary architecture cost is coexistence: modern v1 flows and legacy endpoints/components remain active together.

## Capability and Migration View

| Area | Current maturity | Leading gap | Confidence |
|---|---|---|---|
| Articles | Strong v1 backend and modern frontend precedents | Admin pending/status completion and remaining legacy helpers | High for core flows |
| Catalogues | Strong v1 CRUD, item, picker, PDF, and frontend flows | Numeric legacy identity, SavedList debt, export parity | High for v1 core |
| Japanese material | Public v1 list/detail routes and focused backend tests | Uneven frontend migration and detail/schema alignment | Medium-high |
| Community | Functional legacy post experience plus partial v1 engagement | Post read/write/comment migration; comment update/delete/replies | Medium |
| Contracts | Scramble/OpenAPI/Orval workflow established | Drift when schema inference or generation order is wrong | High on process, per-schema verification required |
| Deployment | Cross-system flow is documented in config | Provider revision/health evidence not inspected | Medium |
| Documentation | Consolidated baseline and validator added | Must be maintained with feature changes | High for structure |

## Architecture Health

### Strengths

- Clear root and scoped contributor guidance.
- Explicit domain/application/HTTP/infrastructure target boundaries.
- Strong focused backend feature-test coverage in migrated modules.
- React Query/generated-client precedents for articles and catalogues.
- Incremental migration backlog with explicit dependencies and non-goals.
- Separate production web/worker deployment awareness.

### Risks

1. Legacy and v1 caller behavior can diverge.
2. OpenAPI inference can produce misleading frontend types.
3. Web and worker deploys can disagree on queued payloads.
4. Community migrations can become too broad unless read, write, moderation, and comments remain sliced.
5. Provider configuration can be mistaken for current operational health.

## Recommended Next Decisions

1. Complete and verify article administrative v1 contracts before migrating dashboard callers.
2. Add the focused catalogue legacy-ID compatibility contract before removing the raw legacy lookup.
3. Resolve Japanese detail aggregate/schema decisions one resource at a time.
4. Start community migration with post reads, then write/moderation, then comments.
5. Define operational evidence for queue-worker revision, job failure, and processing latency.

## Navigation

- [Architecture Description](../architecture-description.md)
- [Current and Target State](../current-target-state.md)
- [Evidence Manifest](../evidence-manifest.md)
- [Validation Report](../validation-report.md)
- [Technical Handoff](./technical-handoff.md)
