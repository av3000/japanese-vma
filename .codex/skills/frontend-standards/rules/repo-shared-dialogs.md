---
title: Modals Use the Shared Native-Dialog Primitives
impact: MEDIUM
impactDescription: one focus, escape and backdrop behaviour everywhere
tags: repo, modals, dialog, accessibility
---

## Modals Use the Shared Native-Dialog Primitives

**Impact: MEDIUM (one focus, escape and backdrop behaviour everywhere)**

Every modal is built from `src/components/shared/DialogModal` or `src/components/shared/Modal`, opened through `useModal` or `useDialog`. Reuse an existing wrapper first: `DeleteInstanceModal`, `ConfirmModal`, `CatalogueBookmarkModal`. Do not write one-off overlay markup. `src/routes/ArticleDetails/ArticleContent/index.tsx` shows several modal controllers composed in one feature.

**Incorrect (hand-rolled overlay; no focus trap, no Escape, no `<dialog>` semantics):**

```tsx
{showDelete && (
	<div className={styles.overlay} onClick={() => setShowDelete(false)}>
		<div className={styles.box}>
			<p>Delete this catalogue?</p>
			<button onClick={onDelete}>Delete</button>
		</div>
	</div>
)}
```

**Correct:**

```tsx
const deleteModal = useModal(deleteDialogRef, { id: 'catalogue-delete-modal' });

<DeleteInstanceModal
	controller={deleteModal}
	instanceName={catalogue.title}
	onDelete={() => deleteMutation.mutate(catalogue.uuid, { onSuccess: () => navigate(CATALOGUE_ROUTES.list) })}
/>
```

Source: `client/AGENTS.md` §5.
