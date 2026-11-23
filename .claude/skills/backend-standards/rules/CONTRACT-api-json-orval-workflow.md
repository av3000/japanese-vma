---
title: Contract Changes Flow Backend Source, then api.json, then Orval
impact: HIGH
impactDescription: the generated client always matches the backend that serves it
tags: contract, api-json, orval, scramble, ci
---

## Contract Changes Flow Backend Source, then api.json, then Orval

**Impact: HIGH (the generated client always matches the backend that serves it)**

**The `api.json` drift part is enforced by backend CI** (step "Check api.json is up to date" in `.github/workflows/backend-ci.yml`, which re-exports with `APP_NAME=Laravel` and `APP_URL=http://localhost` and fails on any diff). Reviewers skip a stale `api.json`; they still check the order and the source of the fix.

When a generated type is wrong, the fix is in the backend Request, Resource or `#[Response]`. Then run `composer openapi`, check the component's `properties` and `required` in `api.json`, and only then run `npm run orval:file` in `client/`, one after the other. Commit the regenerated `api.json` with the backend change. Never patch the generated client or add client-side coercion for a schema bug.

**Incorrect (`client/src/api/catalogues/items.ts`, still in the code: `CatalogueDetailResource` leaves `items` untyped, so every caller casts through `unknown` instead of the Resource being fixed):**

```ts
const items = (catalogue.items as unknown as Array<{ id: number }>).filter((item) => item.id !== itemId);
```

**Correct (fix the Resource, the way `CatalogueDetailEngagementResource` pins its wire types, then export and regenerate; for `items` that means a typed element, see `CONTRACT-fix-schema-at-resource`):**

```php
'is_liked_by_viewer' => (bool) $this->isLikedByViewer,
```

```bash
composer openapi          # processor-api/
npm run orval:file        # client/, after the export finished
```

Source: root `AGENTS.md` §2, `processor-api/AGENTS.md` §5. Client side: `repo-contract-drift-backend-first` in `frontend-standards`.
