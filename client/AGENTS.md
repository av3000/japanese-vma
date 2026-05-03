# AGENTS.md (client)

This file defines **frontend-specific** guidance for changes under `client/`.

## 1) Frontend Direction In This Repo

- **Primary precedent for migrated work:** Article list/detail/create flows are the best current reference for route shape, React Query usage, and shared form composition.
  - `src/routes/ArticlesList/index.tsx`
  - `src/routes/ArticleDetails/index.tsx`
  - `src/routes/ArticleCreate/index.tsx`
  - `src/components/features/articles/ArticleForm.tsx`
  - `src/api/articles/hooks/useInfiniteArticles.ts`
  - `src/api/articles/details.ts`
- **Preferred list-query direction:** `src/routes/Dashboard/DashboardCataloguesPanel.tsx` plus `src/api/catalogues/catalogues.ts` are the current model for list-style routes that should move toward `/v1/catalogues`.
- **Main migration target:** SavedList/custom-list surfaces should move toward catalogue-v1 patterns. Do not spend time modernizing legacy `/list` or `/lists` route trees in place unless the task is explicitly limited to legacy maintenance.
- **Important caveat:** Article is the positive precedent, but not every line in Article is the end state. `src/routes/ArticleDetails/ArticleContent/index.tsx` still contains TODO-marked legacy `apiCall` escape hatches for bookmarks and PDF flows. Treat those as temporary adapters, not as a pattern to spread.

## 2) Route Composition

- Keep route files thin. A route should usually do param parsing, query/mutation hook wiring, and loading/error gating, then hand rendering to feature components.
- Prefer React Router v6 hooks (`useParams`, `useNavigate`, `useSearchParams`) over prop-driven router access.
- When a page has a substantial form or detail body, split it into a feature component instead of keeping all behavior in the route file.
- Favor route-local filter-to-query mapping helpers over mixing raw query-shape logic into JSX.
- For migrated or touched routes, do not add new class components.

## 3) Data Access Boundaries

- Prefer **feature-scoped React Query hooks** for server state.
  - Example: `useInfiniteArticles` wraps pagination details and returns flattened items plus totals.
  - Example: `useArticleQuery` maps detail payloads into route-friendly UI data.
- Keep request code in centralized API modules:
  - generated Orval clients under `src/api/generated/**`
  - typed service adapters such as `src/api/catalogues/catalogues.ts`
- For API-boundary enums and request/response contract types, prefer Orval-generated models under `src/api/generated/model/**`.
- Keep `src/shared/constants/enums.ts` for UI labels, legacy numeric IDs, and app-local helpers; do not use it as a substitute for generated API contract types when the backend schema already defines the enum.
- If generated endpoints are not ready, isolate legacy calls behind a temporary adapter module instead of scattering raw `apiCall(...)` usage through route trees.
- Query keys should be descriptive and stable. Include the meaningful filter object in the key when server state depends on filters or ownership.
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

- Prefer the shared native-dialog primitives over new `react-bootstrap` modal usage on touched or migrated code.
  - `src/components/shared/DialogModal`
  - `src/components/shared/Modal`
  - `src/hooks/useModal.ts`
  - `src/hooks/useDialog.ts`
- Reuse existing modal wrappers before creating one-off modal markup.
  - `src/components/features/DeleteInstanceModal`
  - `src/components/shared/modals/ConfirmModal.tsx`
  - `src/components/features/catalogues/CatalogueBookmarkModal`
- `src/routes/ArticleDetails/ArticleContent/index.tsx` is the best current example of composing multiple modal controllers in one feature without pushing modal markup into unrelated route branches.
- If you touch a migrated surface, do not introduce fresh `react-bootstrap` `Modal` usage there.

## 6) Type Mapping And Display Labels

- Use shared typed enums, constants, or mapping helpers instead of numeric type logic in JSX.
  - `src/shared/constants/enums.ts` is the preferred direction for object-type identifiers and labels.
- If a backend type or list category arrives as a number, map it once in a helper or adapter near the data boundary and pass descriptive values into components.
- Do not copy-paste label arrays like the current SavedList route does.
- Do not spread magic numeric checks such as `type === 2 || type === 6` through rendering code.
- The existing numeric list filters in `src/routes/Dashboard/DashboardCataloguesPanel.tsx` and legacy SavedList components are transitional debt, not a standard to extend.

## 7) Current Legacy Surfaces To Treat As Debt

- The following files are useful mainly as examples of what to migrate away from:
  - `src/routes/SavedLists/index.tsx`
  - `src/routes/SavedListDetails/index.tsx`
  - `src/routes/SavedListForm/index.tsx`
  - `src/routes/SavedListEdit/index.tsx`
  - `src/components/features/SavedList/SavedListItems.tsx`
  - `src/components/features/SavedList/SavedListItem/index.tsx`
- Known issues in those files that should not be copied forward:
  - `@ts-nocheck`
  - class components
  - `componentWillMount`
  - `props.match` / `props.history`
  - raw `apiCall` strings embedded in routes
  - component-owned pagination/search state for server data
  - duplicated list type labels and magic numbers
  - legacy comment props that do not match the current `CommentsBlock` contract
- Additional comment-specific debt to avoid copying forward:
  - `src/routes/community/PostDetails/index.tsx`
  - `src/routes/japanese/SentenceDetails/index.tsx`
- Current comment precedents:
  - `src/routes/ArticleDetails/ArticleContent/index.tsx`
  - `src/routes/CatalogueDetails/CatalogueContent.tsx`
  - Those are the current migrated examples for wiring `CommentsBlock` into detail routes.

## 8) SavedList And Catalogue Migration Guardrails

- Preferred upgrade order:
  1. migrate data access first
  2. hide legacy endpoints behind temporary typed adapters when a v1 endpoint is not ready
  3. keep behavior stable with thin filter/param mapping layers
  4. rebuild the route around catalogue-v1 query patterns instead of modernizing legacy SavedList code in place
- For list-style routes, first ask whether the surface should be backed by `/v1/catalogues`.
- When migrating search/pagination, prefer typed query hooks and flattened query results over manual `next_page_url` state.
- When migrating comments, match the current `CommentsBlock` contract:
  - read props: `readObjectType`, `readObjectUuid`
  - write props: `entityType`, `entityId`, `entityUuid`
- Keep the boundary explicit:
  - reads still follow resource-specific routes like article/catalogue UUID comment endpoints
  - writes use the generic v1 comment payload and should pass generated `ObjectTemplateType` values plus known entity metadata
- If a route still depends on a legacy endpoint, keep that dependency in a dedicated adapter module with a TODO that names the target v1 replacement.
- Preserve user-visible behavior unless a behavior change is explicit and documented.

## 9) Banned Patterns For Touched Or Migrated Code

- `@ts-nocheck`
- new class components
- `componentWillMount`
- `props.match`, `props.history`, or other React Router v5 route props
- reviving Redux patterns for new client state
- page-owned pagination/search state where React Query is the natural fit
- raw string endpoints spread across route trees via `apiCall`
- copy-pasted label/type arrays inside components
- numeric type branching directly in JSX when a typed mapper/helper can own it

## 10) Generated API Contract Drift

When frontend issues involve `src/api/generated/**`, Orval output, or unexpected generated model shapes, treat the problem as a backend/frontend contract issue first, not a client typing workaround.

Before adding frontend coercion or adapters:

- Check the generated TypeScript model.
- Check the OpenAPI schema at `/docs/api.json`.
- Check the backend v1 Resource or response annotation that produced the schema.
- If generated types are wrong, fix the backend schema source and add or update backend schema tests.
- Regenerate Orval types only after the schema is correct.
- Do not hand-edit generated files.
- Only add client normalization when the runtime API intentionally supports multiple wire shapes.

## 11) Quality And Validation Expectations

- Keep diffs focused and migration-friendly.
- Run the relevant verification for the files you changed.
- For frontend code changes, run lint, typecheck, and relevant tests for the touched area.
- For guidance-only or skill-only changes, do a file review and run any applicable markdown/format checks that are available without mutating generated code.
- If environment or tooling prevents a check, report the limitation clearly.
- Call out behavior-impacting changes, integration risks, and temporary adapters in your summary.
