# Local Frontend Precedents

Use these examples when comparing touched frontend work.

## Good Precedents

`client/src/api/articles/hooks/useInfiniteArticles.ts`

- Uses generated `articleIndex(...)`.
- Reuses generated query-key helper.
- Owns page-param logic.
- Returns flattened `articles` and `total`.
- Good example of a deep feature API hook.

`client/src/routes/ArticleDetails/index.tsx`

- Parses route params.
- Calls `useArticleQuery`.
- Handles loading/error gates.
- Delegates rendering to `ArticleContent`.
- Good thin route shell.

`client/src/components/features/articles/ArticleForm.tsx`

- Good migrated form precedent.
- Uses shared form composition, validation, server-error mapping, and submit flow.

`client/src/routes/ArticleCreate/index.tsx`

- Good route-to-form composition precedent.
- Keeps the route focused on navigation and submit wiring.
- Delegates field ownership to the feature form.

`client/src/components/features/DeleteInstanceModal`

- Good reusable feature modal precedent.
- Encapsulates common delete confirmation presentation while callers own the action.

`client/src/components/features/catalogues/CatalogueBookmarkModal`

- Good feature-local modal precedent.
- Keeps bookmark list presentation separate from article detail rendering.

`client/src/components/shared/DialogModal` and `client/src/components/shared/modals/ConfirmModal.tsx`

- Good shared dialog primitive precedents (`repo-shared-dialogs`).

## Mixed Precedents

`client/src/routes/ArticleDetails/ArticleContent/index.tsx`

Copy:

- route/detail split
- detail composition
- modal/controller composition
- current `CommentsBlock` usage
- generated catalogue item writes

Do not copy wholesale:

- inline server mutations
- repeated side-effect handlers
- TODO-marked temporary PDF export logic

## Legacy Debt

Do not copy these as precedents for new or touched migrated work:

- `client/src/routes/SavedLists/index.tsx`
- `client/src/routes/SavedListDetails/index.tsx`
- `client/src/routes/SavedListForm/index.tsx`
- `client/src/routes/SavedListEdit/index.tsx`
- `client/src/components/features/SavedList/SavedListItems.tsx`
- `client/src/components/features/SavedList/SavedListItem/index.tsx`

Debt markers, by rule: `repo-no-ts-nocheck`, `repo-no-class-components`, `repo-router-v6-hooks`, `repo-no-direct-axios`, `repo-no-page-owned-server-state`, `repo-shared-type-labels`, `repo-map-numeric-types-once`, `repo-shared-dialogs`, `repo-forms-rhf-zod`.

Touched SavedList/custom-list work should move toward catalogue-v1 query/detail patterns (`repo-catalogue-v1-migration`).
