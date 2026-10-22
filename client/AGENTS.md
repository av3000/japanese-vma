# AGENTS.md (client)

This file defines **frontend-specific** guidance for changes under `client/`.

For any visual, styling, token or component-choice work, read `client/DESIGN.md` first. It covers what each token means, the colour roles, typography (including Japanese text) and which shared component to use.

**Rules live in the `frontend-standards` skill** (`.claude/skills/frontend-standards/`): one file per rule ID, each with an impact level and an Incorrect/Correct example. This file keeps direction, precedents and process, and names the rule IDs that apply to each area. Cite a rule by its ID.

## 1) Frontend Direction In This Repo

- **Primary precedent for migrated work:** Article list/detail/create flows are the best current reference for route shape, React Query usage, and shared form composition.
  - `src/routes/ArticlesList/index.tsx`
  - `src/routes/ArticleDetails/index.tsx`
  - `src/routes/ArticleCreate/index.tsx`
  - `src/components/features/articles/ArticleForm.tsx`
  - `src/api/articles/hooks/useInfiniteArticles.ts`
  - `src/api/articles/details.ts`
- **Preferred list-query direction:** `src/routes/Dashboard/DashboardCataloguesPanel.tsx` plus `src/api/catalogues/catalogues.ts` are the current model for list-style routes backed by `/v1/catalogues` (`repo-catalogue-v1-migration`).

## 2) Route Composition

- Rules: `repo-thin-routes`, `repo-router-v6-hooks`, `repo-parse-route-params-once`, `repo-no-class-components`.
- Precedent for a thin route: `src/routes/ArticleDetails/index.tsx`.

## 3) Data Access Boundaries

- Rules: `repo-feature-query-hooks`, `repo-generated-client-first`, `repo-no-direct-axios`, `repo-adapter-todo-shape`, `repo-generated-contract-types`, `repo-generated-query-keys`, `client-query-keys`, `repo-no-page-owned-server-state`, `repo-catalogue-for-item-boundary`.
- Precedents:
  - `useInfiniteArticles` wraps pagination details and returns flattened items plus totals.
  - `useArticleQuery` maps detail payloads into route-friendly UI data.
  - Detail adapters such as `src/api/articles/details.ts` or `src/api/catalogues/details.ts` sit between Orval and route components when a route needs mapped detail data plus related mutations.
  - Request code lives in centralized API modules: generated Orval clients under `src/api/generated/**`, and typed service adapters such as `src/api/catalogues/catalogues.ts`.
  - Catalogue for-item reads and writes live in `src/api/catalogues/cataloguesForItem.ts`; the bookmark UI (`AuthorizedBookmarkWidget`, `CatalogueBookmarkModal`) goes through that module.
  - `@/services/axios` is imported only by the Orval mutator (`src/services/orval-mutator.ts`).

## 4) Forms And Validation

- Rule: `repo-forms-rhf-zod`.
- `src/components/features/articles/ArticleForm.tsx` is the current standard for `react-hook-form` field ownership, Zod resolver usage, server-error mapping and submit-state handling.

## 5) Modal And Dialog Composition

- Rules: `repo-shared-dialogs`, `repo-css-modules-tokens`, `repo-layout-primitives`, `repo-no-bootstrap-utility-classes`.
- Primitives: `src/components/shared/DialogModal`, `src/components/shared/Modal`, `src/hooks/useModal.ts`, `src/hooks/useDialog.ts`.
- Existing wrappers: `src/components/features/DeleteInstanceModal`, `src/components/shared/modals/ConfirmModal.tsx`, `src/components/features/catalogues/CatalogueBookmarkModal`.
- `src/routes/ArticleDetails/ArticleContent/index.tsx` is the best current example of composing multiple modal controllers in one feature without pushing modal markup into unrelated route branches.
- `npm run style:audit` reports what is left to migrate off utility classes.

## 6) Type Mapping And Display Labels

- Rules: `repo-map-numeric-types-once`, `repo-shared-type-labels`, `repo-generated-contract-types`.
- `src/shared/constants/enums/` is the preferred direction for object-type identifiers and labels.
- Comments: `repo-comments-block-contract`. Examples: `src/routes/ArticleDetails/ArticleContent/index.tsx`, `src/routes/community/PostDetails/PostContent/index.tsx`, `src/components/features/japanese/related/SentenceRelated.tsx`.

## 7) Banned Patterns For Touched Or Migrated Code

`repo-no-ts-nocheck`, `repo-no-class-components`, `repo-router-v6-hooks`, `repo-no-redux`, `repo-no-page-owned-server-state`, `repo-no-direct-axios`, `repo-shared-type-labels`, `repo-map-numeric-types-once`.

## 8) Generated API Contract Drift

When frontend issues involve `src/api/generated/**`, Orval output, or unexpected generated model shapes, treat the problem as a backend/frontend contract issue first, not a client typing workaround: `repo-contract-drift-backend-first`. The order of steps is in root `AGENTS.md` §2.

- Some generated subfields, especially complex catalogue detail shapes like `items`, may still be less trustworthy than the intended runtime shape; treat that as a backend schema problem first.

## 9) Quality And Validation Expectations

- Keep diffs focused and migration-friendly.
- Run the relevant verification for the files you changed.
- For frontend code changes, run lint, typecheck, and relevant tests for the touched area. Format only the files you touched: `npm run format -- <paths>`.
- In a fresh worktree, run `npm ci` and `npm run orval:file` first; `docs/agents/local-environment.md` lists the other traps (the `.env` values, `node_modules` junctions, sandbox-only failures).
- Test placement: `repo-test-shared-seam`.
- For guidance-only or skill-only changes, do a file review and run any applicable markdown/format checks that are available without mutating generated code.
- If environment or tooling prevents a check, report the limitation clearly.
- Call out behavior-impacting changes, integration risks, and temporary adapters in your summary.
