---
title: New and Refactored Endpoints Go in routes/api_v1.php
impact: MEDIUM
impactDescription: one modern surface while the legacy one keeps working
tags: arch, routes, v1, legacy
---

## New and Refactored Endpoints Go in routes/api_v1.php

**Impact: MEDIUM (one modern surface while the legacy one keeps working)**

New or refactored endpoints are v1 endpoints in `routes/api_v1.php`, built with the v1 layers. A legacy endpoint keeps working until a migration retires it on purpose (as RET-ART-01 retired the legacy article routes); do not break it as a side effect, and do not extend it with new behaviour.

**Incorrect (`635d079c^:routes/api.php`: the legacy article family kept growing actions on numeric ids and string controller refs):**

```php
Route::post('article/{id}/like', 'ArticleController@likeArticle');
Route::post('article/{id}/checklike', 'ArticleController@checkIfLikedArticle');
Route::post('article/{id}/togglepublicity', 'ArticleController@togglePublicity');
```

**Correct (`routes/api_v1.php`: the same need as a v1 endpoint on the uuid):**

```php
Route::post('articles/{uuid}/status', [ArticleController::class, 'setStatus']);
```

Source: `processor-api/AGENTS.md` §1 and §2.
