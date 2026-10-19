---
title: Fix Contract Drift at the Backend Schema, Not in the Client
impact: HIGH
impactDescription: generated types stay trustworthy for every caller
tags: repo, orval, api-json, contract
---

## Fix Contract Drift at the Backend Schema, Not in the Client

**Impact: HIGH (generated types stay trustworthy for every caller)**

When a generated model looks wrong, check the generated type, then `processor-api/api.json`, then the backend Resource or `#[Response]` that produced it. Fix the backend source, regenerate `api.json` (`composer openapi`), and only then run `npm run orval:file`. Never hand-edit `src/api/generated/**`. Add client normalisation only when the runtime API intentionally returns several wire shapes.

**Incorrect (`src/api/catalogues/items.ts`, still in the code: the schema leaves catalogue `items` untyped, so the client casts through `unknown` both ways):**

```ts
const items = (catalogue.items as unknown as Array<{ id: number }>).filter((item) => item.id !== itemId);

return {
	...catalogue,
	items: items as unknown as CatalogueDetailResource['items'],
};
```

**Correct (type `items` at `CatalogueDetailResource` in the backend, regenerate `api.json` and the client; the casts go):**

```ts
const items = catalogue.items.filter((item) => item.id !== itemId);

return { ...catalogue, items };
```

Source: `client/AGENTS.md` §10, root `AGENTS.md` §2.
