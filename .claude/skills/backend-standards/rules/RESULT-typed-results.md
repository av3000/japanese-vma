---
title: v1 Responses Are Resources or TypedResults, Never Ad Hoc JSON
impact: HIGH
impactDescription: one success and one error shape the client can rely on
tags: result, typed-results, responses
---

## v1 Responses Are Resources or TypedResults, Never Ad Hoc JSON

**Impact: HIGH (one success and one error shape the client can rely on)**

A v1 action returns a named Resource (with `$wrap = null`) for entity data, a `TypedResults` helper (`ok`, `created`, `accepted`, `noContent`) for other successes, and `TypedResults::fromError($result->getError())` for every failure. It does not build `response()->json([...])` envelopes by hand.

**Incorrect (legacy `ArticleController::index`):**

```php
return response()->json([
    'test' => true,
    'success' => true,
    'articles' => $articles,
    'message' => 'articles fetched',
    'imagePath' => getArticleImageFromImages('testing-image.jpg'),
]);
```

**Correct (`UserRoleController`):**

```php
if ($result->isFailure()) {
    return TypedResults::fromError($result->getError());
}

return TypedResults::ok(['roles' => RoleResource::collection($roles)]);
```

Source: `.ai/guidelines/backend-response-and-errors.md`.
