# Frontend Scalability Lens

Use this when a review asks whether the frontend architecture scales, or when a proposed seam affects repeated route/API/state/provider patterns.

## Definition

A scalable frontend architecture lets the 20th route, 50th API call, or next migrated feature land without making every change touch routing, API wiring, global state, UI composition, backend contract details, and startup behavior at once.

In this repo, scalable frontend architecture optimizes:

- **Locality:** a feature change mostly stays inside one route/feature/API seam.
- **Clear seams:** route, feature orchestration, feature API, generated client, and temporary adapter responsibilities are distinct.
- **Low coordination cost:** separate features do not constantly collide in shared files.
- **Stable data boundaries:** backend wire shape is absorbed by generated clients, feature API hooks, or small adapters.
- **Boring state ownership:** each kind of state has one owner (see "State Ownership" in `frontend-seams.md`).
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

This scales while new work follows `repo-thin-routes`, `repo-no-page-owned-server-state`, `repo-generated-client-first` and `repo-feature-query-hooks`.

## Scaling Checklist

Check the touched surface against these `frontend-standards` rules, grouped by seam. Each breach is a scaling risk as well as a Standards finding.

| Seam | Rules |
| --- | --- |
| Route | `repo-thin-routes`, `repo-no-page-owned-server-state` |
| Feature | `repo-shared-dialogs`, `repo-forms-rhf-zod` |
| API | `repo-generated-client-first`, `repo-feature-query-hooks`, `repo-generated-query-keys`, `repo-adapter-todo-shape` |
| Migration | `repo-catalogue-v1-migration`, `repo-contract-drift-backend-first` |
| Performance | `async-public-routes-no-auth-wait` |
| Testing | `repo-test-shared-seam` |

Questions no rule answers, so judge them:

- Is feature orchestration separate from server mutation mechanics?
- Can the behavior be tested at a feature API or helper seam?
- Are pure startup/provider helpers testable without a heavy DOM environment?

## Scaling Risks To Call Out

- legacy patterns copied forward (the lint-enforced rules, plus `repo-map-numeric-types-once` and `repo-shared-type-labels`)
- generated-client wrappers that only rename Orval functions (`repo-generated-client-first`)
- temporary adapters that lack a removal target (`repo-adapter-todo-shape`)
- public homepage/list data coupled to auth shell state (`async-public-routes-no-auth-wait`)
- global providers absorbing feature-specific behavior
- broad Context values changing often enough to rerender unrelated consumers
- route-level imports that pull admin/dashboard/heavy tooling into public initial chunks

## Rule Of Thumb

Add a seam only when deleting it would push real complexity into multiple callers. Do not add frontend `domain/`, `useCases/`, `Service`, `Manager`, or `Adapter` folders just to look scalable.
