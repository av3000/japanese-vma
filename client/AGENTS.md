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
- **Important caveat:** `src/routes/ArticleDetails/ArticleContent/index.tsx` is now the positive precedent for catalogue-for-item writes: bookmark add/remove already use generated v1 catalogue item endpoints there. Its remaining TODO-marked legacy escape hatch is PDF export, which should still be treated as temporary adapter debt rather than a pattern to spread.

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
- Keep `src/shared/constants/enums.ts` for UI labels, legacy numeric IDs, and app-local helpers; do not use it as a substitute for generated API contract types when the backend schema already defines the enum.
- Catalogue for-item state is a split boundary in this repo:
  - reads and writes both belong behind `src/api/catalogues/cataloguesForItem.ts`
  - reads should prefer generated v1 catalogue-for-item endpoints when available
  - writes should use generated v1 catalogue item clients (`catalogueAddItem`, `catalogueRemoveItem`) either directly from the route or through a small helper in that same module
  - `elementBelongsToList` is legacy debt; new or touched code should prefer generated for-item fields such as `contains_item`
- Do not route new catalogue-for-item writes through `src/api/catalogues/actions.ts` when a generated v1 client already exists and works.
- If generated endpoints are not ready, isolate legacy calls behind a temporary adapter module instead of scattering raw `apiCall(...)` usage through route trees.
- Temporary adapters are for true transition states only. `actions.ts`-style wrappers are acceptable when the endpoint is intentionally legacy, undocumented, or the generated client is actually unusable. If Orval already generates a stable callable client for a documented v1 endpoint, prefer that generated client instead of adding a parallel wrapper.
- `deleteCatalogue` and legacy PDF export are current examples of acceptable transitional adapters. Catalogue item add/remove are not.
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
- When changing catalogue for-item behavior, check `KanjiDetails`, `WordDetails`, `RadicalDetails`, and `SentenceDetails` together; they share the same legacy pattern and can drift if migrated piecemeal.
- When migrating search/pagination, prefer typed query hooks and flattened query results over manual `next_page_url` state.
- When migrating comments, match the current `CommentsBlock` contract:
  - read props: `readObjectType`, `readObjectUuid`
  - write props: `entityType`, `entityId`, `entityUuid`
- Keep the boundary explicit:
  - reads still follow resource-specific routes like article/catalogue UUID comment endpoints
  - writes use the generic v1 comment payload and should pass generated `ObjectTemplateType` values plus known entity metadata
- If a route still depends on a legacy endpoint, keep that dependency in a dedicated adapter module with a TODO that names the target v1 replacement.
- Typed legacy adapters are acceptable transitional debt when a v1 replacement is missing or the frontend generation/worktree state is not yet aligned.
- Catalogue for-item reads and catalogue PDF export are current examples of dependencies that may remain intentionally legacy behind adapter modules. Catalogue for-item writes should prefer the generated v1 item clients.
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
- Do not run Orval against a stale `api.json`; complete backend schema regeneration first and only then regenerate frontend clients.
- Do not hand-edit generated files.
- Some generated subfields, especially complex catalogue detail shapes like `items`, may still be less trustworthy than the intended runtime shape; treat that as a backend schema problem first.
- Only add client normalization when the runtime API intentionally supports multiple wire shapes.

## 11) Quality And Validation Expectations

- Keep diffs focused and migration-friendly.
- Run the relevant verification for the files you changed.
- For frontend code changes, run lint, typecheck, and relevant tests for the touched area.
- When multiple legacy routes share the same new write helper or adapter boundary, prefer strong unit coverage on the shared helper plus one representative route regression test before duplicating near-identical integration tests across every migrated route.
- For guidance-only or skill-only changes, do a file review and run any applicable markdown/format checks that are available without mutating generated code.
- If environment or tooling prevents a check, report the limitation clearly.
- Call out behavior-impacting changes, integration risks, and temporary adapters in your summary.
