---
title: Concrete Routes Before Parameterised Siblings, With Constraints
impact: MEDIUM
impactDescription: a concrete path is never swallowed by a {uuid} match
tags: contract, routes, api-v1
---

## Concrete Routes Before Parameterised Siblings, With Constraints

**Impact: MEDIUM (a concrete path is never swallowed by a {uuid} match)**

When a concrete v1 route sits beside a parameterised one (`catalogues/for-item` beside `catalogues/{uuid}`), register the concrete route first and constrain the parameter (`->whereUuid('uuid')`). Otherwise `catalogues/legacy` resolves to `show` with uuid `legacy`, `EntityId::from()` throws, and the client gets a 500. Pin the order with a test, as `CatalogueLegacyIdentityV1Test::test_legacy_segment_is_not_consumed_as_a_catalogue_uuid` does.

**Incorrect (the failure mode the `routes/api_v1.php` comment records):**

```php
Route::get('catalogues/{uuid}', [CatalogueController::class, 'show']);
Route::get('catalogues/legacy/{id}', [CatalogueController::class, 'resolveLegacyId']);
// GET catalogues/legacy -> show('legacy') -> InvalidArgumentException -> 500
```

**Correct (`routes/api_v1.php`):**

```php
Route::get('catalogues/legacy/{id}', [CatalogueController::class, 'resolveLegacyId'])
    ->where('id', '[1-9][0-9]{0,17}');
// ...
Route::get('catalogues/for-item', [CatalogueController::class, 'forItem']);
// ...
Route::get('catalogues/{uuid}', [CatalogueController::class, 'show'])
    ->whereUuid('uuid');
```

Source: `processor-api/AGENTS.md` §2.
