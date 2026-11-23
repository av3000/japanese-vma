---
title: Services Return Result With Typed Errors
impact: HIGH
impactDescription: every failure has a code and a status, and none is hidden
tags: result, errors, services
---

## Services Return Result With Typed Errors

**Impact: HIGH (every failure has a code and a status, and none is hidden)**

An application service returns `Result::success($data)` or `Result::failure($error)`, where `$error` is a `ResultError` built by the module's error class (`ArticleErrors::notFound`, `ArticleErrors::accessDenied`). Unexpected exceptions are caught and logged in the service and mapped to a typed failure. Do not return `null` or `false` for a failure, and do not throw or `abort()` across the controller boundary.

**Incorrect (`85705d7c`: a boolean loses the reason, and a raw exception message leaks):**

```php
$deleted = $this->articleService->deleteArticle($articleUuid, auth('api')->user());

if (! $deleted) {
    return response()->json(['success' => false, 'message' => 'Article not found or unauthorized'], 404);
}
// ...
} catch (\Exception $e) {
    return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
}
```

**Correct (`ArticleErrors` and `ArticleService::getArticle`):**

```php
public static function notFound(string $articleUid): ResultError
{
    return new ResultError(
        code: 'Articles.NotFound',
        status: HttpStatus::NOT_FOUND,
        description: 'Article not found',
        detail: "Article with ID {$articleUid} does not exist",
        errorMessage: "Article with ID {$articleUid} does not exist",
    );
}

if (! $article) {
    return Result::failure(ArticleErrors::notFound($articleUid->value()));
}
```

Source: `.ai/guidelines/backend-response-and-errors.md`, `processor-api/AGENTS.md` §5.
