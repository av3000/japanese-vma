# Frontend Seams

Use these frontend-specific seam names consistently with the architecture vocabulary. The rules each seam must follow are `frontend-standards` rule IDs, cited below rather than restated.

## Route Seam

The route seam is where React Router params, search params, navigation, loading gates, and error gates meet the feature. Rules: `repo-thin-routes`, `repo-router-v6-hooks`, `repo-parse-route-params-once`.

Smell: route owns raw request details, pagination mechanics, backend wire shape, cache invalidation, or repeated mutation logic.

## Feature Orchestration Seam

Feature orchestration owns UI flow:

- modal state
- selected item state
- temporary form values
- local display flags
- composition of feature and shared UI modules
- navigation decisions that depend on user action

Smell: orchestration module becomes a server mutation container.

## Feature API Seam

Feature API modules under `src/api/<feature>/...` own server-state behavior:

- query keys
- generated query-key helper reuse
- pagination flattening
- request payload mapping
- response mapping
- cache invalidation
- mutation behavior
- temporary legacy shielding

This is the preferred seam for repeated or behavior-heavy server logic. Rules: `repo-feature-query-hooks`, `repo-generated-query-keys`, `client-query-keys`.

## Generated Client Seam

Generated Orval clients are the transport adapter for documented v1 endpoints. Rules: `repo-generated-client-first`, `repo-contract-drift-backend-first`.

## Temporary Adapter Seam

Temporary adapters are for transition states only:

- legacy endpoint remains necessary
- v1 endpoint is missing
- generated output is unstable or unusable
- backend/schema work is pending

Rule: `repo-adapter-todo-shape`.

## State Ownership

Which tool owns which state: `client-query-dedup` and `repo-no-page-owned-server-state` (server state), `rerender-store-id-derive-item` (selection), `rerender-context-value-memo` (Context), `repo-no-redux`.

Context suits stable app and session dependencies (auth, permissions, config). A selector-based store such as Zustand earns its place only when shared client state changes often, many independent consumers need slices of it, and broad re-renders are measured or clearly likely.
