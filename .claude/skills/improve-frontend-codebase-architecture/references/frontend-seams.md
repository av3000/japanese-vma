# Frontend Seams

Use these frontend-specific seam names consistently with the architecture vocabulary.

## Route Seam

The route seam is where React Router params, search params, navigation, loading gates, and error gates meet the feature.

Good route modules are thin. They parse inputs, call hooks, gate states, and delegate.

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

This is the preferred seam for repeated or behavior-heavy server logic.

## Generated Client Seam

Generated Orval clients are the transport adapter for documented v1 endpoints.

Use them directly when usable. Wrap only when the wrapper adds behavior or shields instability.

## Temporary Adapter Seam

Temporary adapters are for transition states only:

- legacy endpoint remains necessary
- v1 endpoint is missing
- generated output is unstable or unusable
- backend/schema work is pending

Every temporary adapter must name its target, removal condition, and GitHub issue number or `no issue`.
