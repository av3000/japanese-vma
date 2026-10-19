---
name: frontend-standards
description: Citable frontend rules for the client/ React app (Vite SPA, React 19.1, React Router v6, TanStack Query v5, Orval-generated clients, CSS Modules). Each rule has a stable ID, an impact level and an Incorrect/Correct example. Use when writing, reviewing or refactoring code under client/, and whenever a review needs to cite a frontend rule (review-branch loads it for client/ diffs). Covers data fetching and query keys, generated-client boundaries, route composition, forms, modals, styling tokens and accessibility, bundle size, re-renders and rendering performance.
---

# Frontend standards

Rules for code under `client/`. Each rule is one file in `rules/`, named after its ID. Generic React rules come first from Vercel's `react-best-practices` and `composition-patterns`, pruned and rewritten for this stack; `repo-*` rules codify `client/AGENTS.md` and `client/DESIGN.md`.

## How to use it

- **Writing code:** scan the index for the area you are touching and open those rule files. `repo-*` rules win over a generic rule when they disagree.
- **Citing a rule in a review:** `<rule-id> (<IMPACT>) path:line`, for example `repo-generated-client-first (HIGH) client/src/routes/CataloguesList/index.tsx:42`. Quote the rule's one-line summary, not a paraphrase.
- **Skip rules marked "Enforced by …"** in the rule file (and *(enforced by tooling)* below). ESLint, `tsc` or `npm run style:audit` reports those; a reviewer does not.
- **Prose stays in `client/AGENTS.md` and `client/DESIGN.md`:** they explain direction, precedents and process, and point to rule IDs here instead of restating them.

## Impact and review criticality

| Impact | What it means | Criticality of a breach (`review-branch`) |
| --- | --- | --- |
| CRITICAL | Large, user-visible cost: waterfalls, big bundles, unmaintainable component APIs | Warning by default; Critical when it causes wrong behaviour |
| HIGH | Real correctness, contract, accessibility or performance cost | Warning by default; Critical when it causes wrong behaviour |
| MEDIUM | Consistency and moderate performance | Suggestion |
| LOW | Micro-optimisation or local tidiness | Suggestion |

Upstream uses two in-between levels; they are rounded down here: MEDIUM-HIGH is MEDIUM, LOW-MEDIUM is LOW.

## Rule format

Frontmatter `title`, `impact` (CRITICAL, HIGH, MEDIUM or LOW), optional `impactDescription`, and `tags`. Then the rule, an **Incorrect** block, a **Correct** block, optional references, and a source line. A rule that tooling checks says **Enforced by …** under its heading. Adding a rule: new file named after the ID, an entry below under its impact, examples shaped like this repo's code.

## Source

- **Upstream:** [vercel-labs/agent-skills](https://github.com/vercel-labs/agent-skills) at commit `063bee94c3f4df8453406c830b0a7df0f2860278` (MIT): `skills/react-best-practices` and four rules from `skills/composition-patterns`.
- **What was dropped, rewritten and added, and how to re-sync:** [references/seeding.md](references/seeding.md).

## Rules by impact

### CRITICAL (4)

- [`architecture-avoid-boolean-props`](rules/architecture-avoid-boolean-props.md): Avoid Boolean Prop Proliferation
- [`async-dependencies`](rules/async-dependencies.md): Dependency-Based Parallelization
- [`async-parallel`](rules/async-parallel.md): Promise.all() for Independent Operations
- [`bundle-dynamic-imports`](rules/bundle-dynamic-imports.md): Lazy-Load Heavy Components With React.lazy

### HIGH (24)

- [`architecture-compound-components`](rules/architecture-compound-components.md): Use Compound Components
- [`async-cheap-condition-before-await`](rules/async-cheap-condition-before-await.md): Check Cheap Conditions Before Async Flags
- [`async-defer-await`](rules/async-defer-await.md): Defer Await Until Needed
- [`async-fetch-race-cleanup`](rules/async-fetch-race-cleanup.md): Guard Fetch Effects Against Race Conditions
- [`async-public-routes-no-auth-wait`](rules/async-public-routes-no-auth-wait.md): Public Routes Do Not Wait for Auth
- [`async-suspense-boundaries`](rules/async-suspense-boundaries.md): Suspense Only Around What Actually Suspends
- [`bundle-analyzable-paths`](rules/bundle-analyzable-paths.md): Prefer Statically Analyzable Paths
- [`bundle-conditional`](rules/bundle-conditional.md): Conditional Module Loading
- [`client-query-keys`](rules/client-query-keys.md): Query Keys Name Every Input
- [`rendering-content-visibility`](rules/rendering-content-visibility.md): CSS content-visibility for Long Lists
- [`rendering-resource-hints`](rules/rendering-resource-hints.md): Use React DOM Resource Hints
- [`rendering-script-defer-async`](rules/rendering-script-defer-async.md): Use defer or async on Script Tags
- [`repo-colour-not-only-cue`](rules/repo-colour-not-only-cue.md): Colour Is Never the Only Cue
- [`repo-contract-drift-backend-first`](rules/repo-contract-drift-backend-first.md): Fix Contract Drift at the Backend Schema, Not in the Client
- [`repo-generated-client-first`](rules/repo-generated-client-first.md): Call Documented v1 Endpoints Through the Generated Client
- [`repo-keep-focus-outline`](rules/repo-keep-focus-outline.md): Keep the Global Focus Outline
- [`repo-no-bootstrap-utility-classes`](rules/repo-no-bootstrap-utility-classes.md): No Bootstrap, Tailwind or Utility Classes *(enforced by tooling)*
- [`repo-no-class-components`](rules/repo-no-class-components.md): No Class Components
- [`repo-no-direct-axios`](rules/repo-no-direct-axios.md): No Direct axios Calls or Raw Endpoint Strings
- [`repo-no-page-owned-server-state`](rules/repo-no-page-owned-server-state.md): No Page-Owned Pagination, Search or Loading State for Server Data
- [`repo-no-redux`](rules/repo-no-redux.md): No New Redux State
- [`repo-no-ts-nocheck`](rules/repo-no-ts-nocheck.md): No @ts-nocheck
- [`repo-router-v6-hooks`](rules/repo-router-v6-hooks.md): Use React Router v6 Hooks, Not v5 Route Props
- [`rerender-no-inline-components`](rules/rerender-no-inline-components.md): Don't Define Components Inside Components

### MEDIUM (51)

- [`bundle-barrel-imports`](rules/bundle-barrel-imports.md): Avoid Barrel File Imports
- [`bundle-defer-third-party`](rules/bundle-defer-third-party.md): Defer Non-Critical Third-Party Libraries
- [`bundle-preload`](rules/bundle-preload.md): Preload Based on User Intent
- [`client-external-store-sync`](rules/client-external-store-sync.md): Subscribe to External Stores With useSyncExternalStore
- [`client-localstorage-schema`](rules/client-localstorage-schema.md): Version and Minimize localStorage Data
- [`client-passive-event-listeners`](rules/client-passive-event-listeners.md): Use Passive Event Listeners for Scrolling Performance
- [`client-query-dedup`](rules/client-query-dedup.md): Let TanStack Query Own Server State
- [`client-query-options`](rules/client-query-options.md): Use enabled, staleTime and select Deliberately
- [`client-query-pending-vs-fetching`](rules/client-query-pending-vs-fetching.md): isPending for First Load, isFetching for Background Refetch
- [`js-batch-dom-css`](rules/js-batch-dom-css.md): Avoid Layout Thrashing
- [`js-cache-function-results`](rules/js-cache-function-results.md): Cache Repeated Function Calls
- [`js-length-check-first`](rules/js-length-check-first.md): Early Length Check for Array Comparisons
- [`js-request-idle-callback`](rules/js-request-idle-callback.md): Defer Non-Critical Work with requestIdleCallback
- [`js-tosorted-immutable`](rules/js-tosorted-immutable.md): Use toSorted() Instead of sort() for Immutability
- [`patterns-explicit-variants`](rules/patterns-explicit-variants.md): Create Explicit Component Variants
- [`react19-no-forwardref`](rules/react19-no-forwardref.md): React 19 API Changes
- [`repo-adapter-todo-shape`](rules/repo-adapter-todo-shape.md): A Temporary Adapter Names Its Reason, Target and Removal
- [`repo-assets-through-build`](rules/repo-assets-through-build.md): Ship Assets Through the Build, With Fallbacks for New CSS
- [`repo-catalogue-for-item-boundary`](rules/repo-catalogue-for-item-boundary.md): Catalogue For-Item Reads and Writes Live in cataloguesForItem.ts
- [`repo-catalogue-v1-migration`](rules/repo-catalogue-v1-migration.md): Migrate List Surfaces Onto Catalogue v1, Data Access First
- [`repo-comments-block-contract`](rules/repo-comments-block-contract.md): Wire Comments Through CommentsBlock's Current Props
- [`repo-css-modules-tokens`](rules/repo-css-modules-tokens.md): Style With CSS Modules and Existing Tokens
- [`repo-feature-query-hooks`](rules/repo-feature-query-hooks.md): Server State Goes Through Feature-Scoped Query Hooks
- [`repo-forms-rhf-zod`](rules/repo-forms-rhf-zod.md): Forms Use react-hook-form With a Zod Schema
- [`repo-generated-contract-types`](rules/repo-generated-contract-types.md): API Contract Types Come From the Generated Models
- [`repo-generated-query-keys`](rules/repo-generated-query-keys.md): Reuse the Generated Query-Key Helpers
- [`repo-lang-ja`](rules/repo-lang-ja.md): Japanese Text Carries lang="ja"
- [`repo-layout-primitives`](rules/repo-layout-primitives.md): Lay Out With Container, Stack, Cluster and Grid
- [`repo-map-numeric-types-once`](rules/repo-map-numeric-types-once.md): Map Numeric Types Once, Near the Data Boundary
- [`repo-parse-route-params-once`](rules/repo-parse-route-params-once.md): Parse Route Params Once, Near the Top
- [`repo-reduced-motion`](rules/repo-reduced-motion.md): Respect prefers-reduced-motion
- [`repo-shared-components-first`](rules/repo-shared-components-first.md): Reuse the Shared Components Before Writing Markup
- [`repo-shared-dialogs`](rules/repo-shared-dialogs.md): Modals Use the Shared Native-Dialog Primitives
- [`repo-thin-routes`](rules/repo-thin-routes.md): Keep Route Files Thin
- [`rerender-context-value-memo`](rules/rerender-context-value-memo.md): Memoize Context Values and Split Contexts
- [`rerender-defer-reads`](rules/rerender-defer-reads.md): Defer State Reads to Usage Point
- [`rerender-derived-state-no-effect`](rules/rerender-derived-state-no-effect.md): Calculate Derived State During Rendering
- [`rerender-derived-state`](rules/rerender-derived-state.md): Subscribe to Derived State
- [`rerender-functional-setstate`](rules/rerender-functional-setstate.md): Use Functional setState Updates
- [`rerender-lazy-state-init`](rules/rerender-lazy-state-init.md): Use Lazy State Initialization
- [`rerender-list-keys`](rules/rerender-list-keys.md): Use Stable List Keys, Not Array Indexes
- [`rerender-memo-with-default-value`](rules/rerender-memo-with-default-value.md): Extract Default Non-primitive Parameter Value from Memoized Component to Constant
- [`rerender-memo`](rules/rerender-memo.md): Extract to Memoized Components
- [`rerender-move-effect-to-event`](rules/rerender-move-effect-to-event.md): Put Interaction Logic in Event Handlers
- [`rerender-no-effect-chains`](rules/rerender-no-effect-chains.md): Avoid Chains of Effects That Set State
- [`rerender-reset-state-with-key`](rules/rerender-reset-state-with-key.md): Reset State With a key, Not an Effect
- [`rerender-split-combined-hooks`](rules/rerender-split-combined-hooks.md): Split Combined Hook Computations
- [`rerender-store-id-derive-item`](rules/rerender-store-id-derive-item.md): Store the Selected ID, Derive the Item
- [`rerender-transitions`](rules/rerender-transitions.md): Use Transitions for Non-Urgent Updates
- [`rerender-use-deferred-value`](rules/rerender-use-deferred-value.md): Use useDeferredValue for Expensive Derived Renders
- [`rerender-use-ref-transient-values`](rules/rerender-use-ref-transient-values.md): Use useRef for Transient Values

### LOW (23)

- [`advanced-event-handler-refs`](rules/advanced-event-handler-refs.md): Store Event Handlers in Refs
- [`advanced-init-once`](rules/advanced-init-once.md): Initialize App Once, Not Per Mount
- [`client-event-listeners`](rules/client-event-listeners.md): Deduplicate Global Event Listeners
- [`js-cache-property-access`](rules/js-cache-property-access.md): Cache Property Access in Loops
- [`js-cache-storage`](rules/js-cache-storage.md): Cache Storage API Calls
- [`js-combine-iterations`](rules/js-combine-iterations.md): Combine Multiple Array Iterations
- [`js-early-exit`](rules/js-early-exit.md): Early Return from Functions
- [`js-flatmap-filter`](rules/js-flatmap-filter.md): Use flatMap to Map and Filter in One Pass
- [`js-hoist-regexp`](rules/js-hoist-regexp.md): Hoist RegExp Creation
- [`js-index-maps`](rules/js-index-maps.md): Build Index Maps for Repeated Lookups
- [`js-min-max-loop`](rules/js-min-max-loop.md): Use Loop for Min/Max Instead of Sort
- [`js-set-map-lookups`](rules/js-set-map-lookups.md): Use Set/Map for O(1) Lookups
- [`rendering-animate-svg-wrapper`](rules/rendering-animate-svg-wrapper.md): Animate SVG Wrapper Instead of SVG Element
- [`rendering-conditional-render`](rules/rendering-conditional-render.md): Use Explicit Conditional Rendering
- [`rendering-hoist-jsx`](rules/rendering-hoist-jsx.md): Hoist Static JSX Elements
- [`rendering-svg-precision`](rules/rendering-svg-precision.md): Optimize SVG Precision
- [`rendering-usetransition-loading`](rules/rendering-usetransition-loading.md): Use useTransition Over Manual Loading States
- [`repo-accent-means-level`](rules/repo-accent-means-level.md): The Accent Colour Means JLPT Level
- [`repo-shared-type-labels`](rules/repo-shared-type-labels.md): Labels Come From Shared Constants, Not Copied Arrays
- [`repo-test-shared-seam`](rules/repo-test-shared-seam.md): Test the Shared Helper Once, Plus One Representative Route
- [`rerender-dependencies`](rules/rerender-dependencies.md): Narrow Effect Dependencies
- [`rerender-lift-or-pass-down`](rules/rerender-lift-or-pass-down.md): Do Not Sync Parent and Child State Through Effects
- [`rerender-simple-expression-in-memo`](rules/rerender-simple-expression-in-memo.md): Do not wrap a simple expression with a primitive result type in useMemo
