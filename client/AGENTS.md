# AGENTS.md (client)

This file defines **frontend-specific** guidance for changes under `client/`.

For any visual, styling, token or component-choice work, read `client/DESIGN.md` first. It covers what each token means, the colour roles, typography (including Japanese text) and which shared component to use.

## 1) Frontend Direction In This Repo

- **Primary precedent for migrated work:** Article list/detail/create flows are the best current reference for route shape, React Query usage, and shared form composition.
  - `src/routes/ArticlesList/index.tsx`
  - `src/routes/ArticleDetails/index.tsx`
  - `src/routes/ArticleCreate/index.tsx`
  - `src/components/features/articles/ArticleForm.tsx`
  - `src/api/articles/hooks/useInfiniteArticles.ts`
  - `src/api/articles/details.ts`
- **Preferred list-query direction:** `src/routes/Dashboard/DashboardCataloguesPanel.tsx` plus `src/api/catalogues/catalogues.ts` are the current model for list-style routes backed by `/v1/catalogues`.

## 2) Route Composition

- Keep route files thin. A route should usually do param parsing, query/mutation hook wiring, and loading/error gating, then hand rendering to feature components.
- Prefer React Router v6 hooks (`useParams`, `useNavigate`, `useSearchParams`) over prop-driven router access.
- When `useParams()` values feed v1 writes on touched legacy detail routes, coerce them once near the top of the route with `Number(...)`, reuse that parsed `entityId`, and skip the write if coercion fails.
- When a page has a substantial form or detail body, split it into a feature component instead of keeping all behavior in the route file.
- Favor route-local filter-to-query mapping helpers over mixing raw query-shape logic into JSX.
- For migrated or touched routes, do not add new class components.

## 3) Data Access Boundaries

- Prefer **feature-scoped React Query hooks** for server state.
  - Example: `useInfiniteArticles` wraps pagination details and returns flattened items plus totals.
  - Example: `useArticleQuery` maps detail payloads into route-friendly UI data.
  - Example: detail adapters such as `src/api/articles/details.ts` or `src/api/catalogues/details.ts` should sit between Orval and route components when a route needs mapped detail data plus related mutations.
- Keep request code in centralized API modules:
  - generated Orval clients under `src/api/generated/**`
  - typed service adapters such as `src/api/catalogues/catalogues.ts`
- For API-boundary enums and request/response contract types, prefer Orval-generated models under `src/api/generated/model/**`.
- Keep `src/shared/constants/enums/` for UI labels, legacy numeric IDs, and app-local helpers; do not use it as a substitute for generated API contract types when the backend schema already defines the enum.
- Catalogue for-item reads and writes live in `src/api/catalogues/cataloguesForItem.ts`, on the generated v1 clients (`catalogueAddItem`, `catalogueRemoveItem`) and generated fields such as `contains_item`. The bookmark UI (`AuthorizedBookmarkWidget`, `CatalogueBookmarkModal`) goes through that module.
- Go through a generated client. `@/services/axios` is imported only by the Orval mutator (`src/services/orval-mutator.ts`). If an endpoint is missing from the schema, fix the backend schema first (section 8). Only when that is impossible, add a named temporary adapter module with a TODO that names the v1 replacement, instead of raw endpoint strings in routes.
- If backend-generated query params are awkward or unstable, hide that wire-shape behind the shared adapter boundary instead of leaking it into route code.
- Query keys should be descriptive and stable. Include the meaningful filter object in the key when server state depends on filters or ownership.
- When a custom hook wraps an Orval show endpoint, reuse the generated query key helper such as `getXShowQueryKey(...)` for invalidation instead of inventing a parallel key.
- Avoid page-owned pagination/search/loading state when React Query already fits the problem.

## 4) Forms And Validation

- For new or migrated forms, prefer `react-hook-form` plus Zod.
- Reuse shared form components when the feature already has one.
  - `src/components/features/articles/ArticleForm.tsx` is the current standard for:
    - `react-hook-form` field ownership
    - Zod resolver usage
    - server-error mapping
    - submit-state handling
- Keep request/response types aligned with Zod schemas and generated API models where practical.
- Avoid ad hoc `useState` form objects, inline string validation, and untyped submit payload building on migrated surfaces.

## 5) Modal And Dialog Composition

- Use the shared native-dialog primitives for every modal. Bootstrap and React-Bootstrap are removed from the client; ESLint rejects their imports.
  - `src/components/shared/DialogModal`
  - `src/components/shared/Modal`
  - `src/hooks/useModal.ts`
  - `src/hooks/useDialog.ts`
- Reuse existing modal wrappers before creating one-off modal markup.
  - `src/components/features/DeleteInstanceModal`
  - `src/components/shared/modals/ConfirmModal.tsx`
  - `src/components/features/catalogues/CatalogueBookmarkModal`
- `src/routes/ArticleDetails/ArticleContent/index.tsx` is the best current example of composing multiple modal controllers in one feature without pushing modal markup into unrelated route branches.
- Style with CSS Modules and the shared layout primitives in `src/components/shared/layout` (`Container`, `Stack`, `Cluster`, `Grid`); do not add Bootstrap- or Tailwind-style utility classes to `className`. `npm run style:audit` reports what is left to migrate.

## 6) Type Mapping And Display Labels

- Use shared typed enums, constants, or mapping helpers instead of numeric type logic in JSX.
  - `src/shared/constants/enums/` is the preferred direction for object-type identifiers and labels.
- If a backend type or list category arrives as a number, map it once in a helper or adapter near the data boundary and pass descriptive values into components.
- Do not copy-paste label arrays into components.
- Do not spread magic numeric checks such as `type === 2 || type === 6` through rendering code.
- Comments: render `CommentsBlock` with `parent`, `entityId` and `entityUuid`. `parent` selects the read transport, cache key and entity template together. Examples: `src/routes/ArticleDetails/ArticleContent/index.tsx`, `src/routes/community/PostDetails/PostContent/index.tsx`, `src/components/features/japanese/related/SentenceRelated.tsx`.

## 7) Banned Patterns For Touched Or Migrated Code

- `@ts-nocheck`
- new class components
- `componentWillMount`
- `props.match`, `props.history`, or other React Router v5 route props
- reviving Redux patterns for new client state
- page-owned pagination/search state where React Query is the natural fit
- raw string endpoints spread across route trees, or direct `@/services/axios` calls outside a named transitional adapter
- copy-pasted label/type arrays inside components
- numeric type branching directly in JSX when a typed mapper/helper can own it

## 8) Generated API Contract Drift

When frontend issues involve `src/api/generated/**`, Orval output, or unexpected generated model shapes, treat the problem as a backend/frontend contract issue first, not a client typing workaround.

Before adding frontend coercion or adapters:

- Check the generated TypeScript model.
- Check the OpenAPI schema at `/docs/api.json`.
- Check the backend v1 Resource or response annotation that produced the schema.
- If generated types are wrong, fix the backend schema source and add or update backend schema tests.
- Regenerate Orval types only after the schema is correct.
- Do not run Orval against a stale `api.json`; complete backend schema regeneration first and only then regenerate frontend clients.
- Do not hand-edit generated files.
- Some generated subfields, especially complex catalogue detail shapes like `items`, may still be less trustworthy than the intended runtime shape; treat that as a backend schema problem first.
- Only add client normalization when the runtime API intentionally supports multiple wire shapes.

## 9) Quality And Validation Expectations

- Keep diffs focused and migration-friendly.
- Run the relevant verification for the files you changed.
- For frontend code changes, run lint, typecheck, and relevant tests for the touched area. Format only the files you touched: `npm run format -- <paths>`.
- In a fresh worktree, run `npm ci` and `npm run orval:file` first; `docs/agents/local-environment.md` lists the other traps (the `.env` values, `node_modules` junctions, sandbox-only failures).
- When multiple legacy routes share the same new write helper or adapter boundary, prefer strong unit coverage on the shared helper plus one representative route regression test before duplicating near-identical integration tests across every migrated route.
- For guidance-only or skill-only changes, do a file review and run any applicable markdown/format checks that are available without mutating generated code.
- If environment or tooling prevents a check, report the limitation clearly.
- Call out behavior-impacting changes, integration risks, and temporary adapters in your summary.
