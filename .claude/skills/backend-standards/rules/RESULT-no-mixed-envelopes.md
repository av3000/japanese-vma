---
title: One Endpoint Family, One Response Style
impact: MEDIUM
impactDescription: the client handles one error shape per resource
tags: result, legacy, envelope, migration
---

## One Endpoint Family, One Response Style

**Impact: MEDIUM (the client handles one error shape per resource)**

Do not mix legacy `{success, message}` JSON with Resources and `TypedResults` problem details in one endpoint family. When an endpoint moves to v1, its success and failure paths both move. Known debt: `ArticleController::destroy` still answers through `legacyFailure()` and a hand-built success body; do not copy it.

**Incorrect (`ArticleController::destroy`, while its siblings use `TypedResults::fromError`):**

```php
if ($result->isFailure()) {
    return $this->legacyFailure($result);
}

return response()->json([
    'success' => true,
    'message' => 'Article deleted successfully',
]);
```

**Correct (`CatalogueController` delete: the same style as every other catalogue action):**

```php
if ($result->isFailure()) {
    return TypedResults::fromError($result->getError());
}

return TypedResults::noContent();
```

Source: `.ai/guidelines/backend-response-and-errors.md`.
