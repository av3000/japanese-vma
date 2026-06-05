# Frontend Scalability Lens

Use this when a review asks whether the frontend architecture scales, or when a proposed seam affects repeated route/API/state/provider patterns.

## Definition

A scalable frontend architecture lets the 20th route, 50th API call, or next migrated feature land without making every change touch routing, API wiring, global state, UI composition, backend contract details, and startup behavior at once.

In this repo, scalable frontend architecture optimizes:

- **Locality:** a feature change mostly stays inside one route/feature/API seam.
- **Clear seams:** route, feature orchestration, feature API, generated client, and temporary adapter responsibilities are distinct.
- **Low coordination cost:** separate features do not constantly collide in shared files.
- **Stable data boundaries:** backend wire shape is absorbed by generated clients, feature API hooks, or small adapters.
- **Boring state ownership:** React Query owns server state; local state owns local UI; Context stays stable and infrequent; selector stores need measured/shared-frequency justification.
- **Migration tolerance:** legacy and v1 paths can coexist without making legacy patterns the default for new work.
- **Testable seams:** important behavior can be tested at API hook/helper/feature seams instead of only through route integration.
- **Performance that scales with features:** new features do not inflate the initial bundle, block public views on private data, or expand provider rerender blast radius.

## Current Repo Scale Judgment

Use this wording pattern instead of a yes/no verdict:

```text
Current size: <workable / strained / overbuilt> because ...
Near-term scale: <good / risky> if new work follows ...
Long-term risk: <specific coordination, migration, state, or bundle risk>
```

For `japanese-vma`, the current best direction is:

```text
route -> feature orchestration -> feature API module/hook -> generated client
```

This scales when route files stay thin, React Query owns server state, generated v1 clients remain the default transport, and feature API modules own mapping/invalidation/legacy shielding when that behavior would otherwise repeat.

## Scaling Checklist

### Route

- Does the route mostly parse params/search, call hooks, gate loading/error, and delegate rendering?
- Is server pagination/search/cache invalidation outside JSX?
- Are React Router v6 hooks used instead of legacy route props?

### Feature

- Is local UI flow owned near the feature?
- Are modals, forms, selected items, and temporary display state colocated?
- Is feature orchestration separate from server mutation mechanics?

### API

- Are documented v1 endpoints called through generated clients where usable?
- Does a feature API hook/module own query keys, invalidation, response mapping, and legacy shielding when repeated?
- Are temporary adapters marked with target, removal condition, and issue/no-issue status?

### State

- Is server state in React Query?
- Is local UI state local?
- Is Context limited to stable app/session dependencies?
- Is selector-store usage justified by frequent shared updates or measured broad rerenders?

### Migration

- Is legacy behavior preserved intentionally?
- Are new patterns based on Article/Catalogue v1 precedents instead of SavedList legacy code?
- Is backend contract drift fixed at schema/source when generated types are wrong?

### Performance

- Does the route or feature avoid growing the initial bundle?
- Are private, admin, editor, chart, media, or heavy detail features lazy-loaded?
- Do public views avoid waiting on auth/user/profile queries unless required?
- Are provider values and startup imports kept from expanding rerender or first-load cost?

### Testing

- Can behavior be tested at a feature API/helper seam?
- Is there one representative route regression when several routes share the same adapter?
- Are pure startup/provider helpers tested without requiring a heavy DOM environment when possible?

## Scaling Risks To Call Out

- legacy `apiCall(...)`, `@ts-nocheck`, class components, route-owned pagination/search, and copied label/type logic
- generated-client wrappers that only rename Orval functions
- temporary adapters that lack a removal target
- public homepage/list data coupled to auth shell state
- global providers absorbing feature-specific behavior
- broad Context values changing often enough to rerender unrelated consumers
- route-level imports that pull admin/dashboard/heavy tooling into public initial chunks

## Rule Of Thumb

Add a seam only when deleting it would push real complexity into multiple callers. Do not add frontend `domain/`, `useCases/`, `Service`, `Manager`, or `Adapter` folders just to look scalable.
