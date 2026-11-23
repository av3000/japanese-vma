---
title: Array Query Params Have Clean Rule Keys
impact: LOW
impactDescription: readable Request rules and stable validation error keys
tags: contract, requests, query-params, orval
---

## Array Query Params Have Clean Rule Keys

**Impact: LOW (readable Request rules and stable validation error keys)**

Name array query params in the Request without brackets: `'types' => 'sometimes|array'` plus `'types.*' => 'integer'`, not a `'types[]'` key, unless legacy compatibility needs the bracket form. Validation errors then come back as `types.0`. Note that Scramble still documents every array query param with `[]` in `api.json` (`types[]`, `jlpt_levels[]`), so the generated Orval params keep the bracket name; that wire name is Scramble's, not the Request's.

**Incorrect (hypothetical, no instance in the repo: the bracket form baked into the rule key):**

```php
return [
    'item_id' => 'required|integer',
    'types[]' => 'sometimes|array',
];
```

**Correct (`IndexCataloguesForItemRequest`):**

```php
return [
    'item_id' => 'required|integer',
    'types' => 'sometimes|array',
    'types.*' => 'integer',
    'search' => 'sometimes|string|min:2',
];
```

Source: `processor-api/AGENTS.md` §4.
