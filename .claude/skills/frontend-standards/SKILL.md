---
name: frontend-standards
description: Citable frontend rules for the client/ React app (Vite SPA, React 19.1, React Router v6, TanStack Query v5, Orval-generated clients, CSS Modules). Each rule has a stable ID, an impact level and an Incorrect/Correct example. Use when writing, reviewing or refactoring code under client/, and whenever a review needs to cite a frontend rule (review-branch loads it for client/ diffs). Covers data fetching and query keys, generated-client boundaries, route composition, forms, modals, styling tokens, accessibility, and effects that should not be effects.
---

# Frontend standards

Rules for code under `client/`. Each rule is one file in `rules/`, named after its ID. Most are `repo-*` rules that codify `client/AGENTS.md` and `client/DESIGN.md`. The few generic rules left cover TanStack Query and effect mistakes the model still makes; generic React advice it already follows is left out on purpose.

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

- **Upstream:** [vercel-labs/agent-skills](https://github.com/vercel-labs/agent-skills) at commit `063bee94c3f4df8453406c830b0a7df0f2860278` (MIT): `skills/react-best-practices`. Most upstream rules were dropped; see seeding.md.
- **What was dropped, rewritten and added, and how to re-sync:** [references/seeding.md](references/seeding.md).

## Rules by impact

### HIGH (9)

- [`async-public-routes-no-auth-wait`](rules/async-public-routes-no-auth-wait.md): Public Routes Do Not Wait for Auth
- [`client-query-keys`](rules/client-query-keys.md): Query Keys Name Every Input
- [`repo-colour-not-only-cue`](rules/repo-colour-not-only-cue.md): Colour Is Never the Only Cue
- [`repo-contract-drift-backend-first`](rules/repo-contract-drift-backend-first.md): Fix Contract Drift at the Backend Schema, Not in the Client
- [`repo-generated-client-first`](rules/repo-generated-client-first.md): Call Documented v1 Endpoints Through the Generated Client
- [`repo-keep-focus-outline`](rules/repo-keep-focus-outline.md): Keep the Global Focus Outline
- [`repo-no-direct-axios`](rules/repo-no-direct-axios.md): No Direct axios Calls or Raw Endpoint Strings *(enforced by tooling)*
- [`repo-no-page-owned-server-state`](rules/repo-no-page-owned-server-state.md): No Page-Owned Pagination, Search or Loading State for Server Data
- [`repo-no-ts-nocheck`](rules/repo-no-ts-nocheck.md): No @ts-nocheck *(enforced by tooling)*

### MEDIUM (22)

- [`client-query-pending-vs-fetching`](rules/client-query-pending-vs-fetching.md): isPending for First Load, isFetching for Background Refetch
- [`repo-adapter-todo-shape`](rules/repo-adapter-todo-shape.md): A Temporary Adapter Names Its Reason, Target and Removal
- [`repo-assets-through-build`](rules/repo-assets-through-build.md): Ship Assets Through the Build, With Fallbacks for New CSS
- [`repo-catalogue-for-item-boundary`](rules/repo-catalogue-for-item-boundary.md): Catalogue For-Item Reads and Writes Live in cataloguesForItem.ts
- [`repo-catalogue-v1-migration`](rules/repo-catalogue-v1-migration.md): Migrate List Surfaces Onto Catalogue v1, Data Access First
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
- [`rerender-derived-state-no-effect`](rules/rerender-derived-state-no-effect.md): Calculate Derived State During Rendering
- [`rerender-move-effect-to-event`](rules/rerender-move-effect-to-event.md): Put Interaction Logic in Event Handlers
- [`rerender-no-effect-chains`](rules/rerender-no-effect-chains.md): Avoid Chains of Effects That Set State
- [`rerender-reset-state-with-key`](rules/rerender-reset-state-with-key.md): Reset State With a key, Not an Effect

### LOW (4)

- [`repo-accent-means-level`](rules/repo-accent-means-level.md): The Accent Colour Means JLPT Level
- [`repo-shared-type-labels`](rules/repo-shared-type-labels.md): Labels Come From Shared Constants, Not Copied Arrays
- [`repo-test-shared-seam`](rules/repo-test-shared-seam.md): Test the Shared Helper Once, Plus One Representative Route
- [`rerender-lift-or-pass-down`](rules/rerender-lift-or-pass-down.md): Do Not Sync Parent and Child State Through Effects
