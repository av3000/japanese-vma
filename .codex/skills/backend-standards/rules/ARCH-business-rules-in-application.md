---
title: Business Rules and Ownership Checks Live in the Application Layer
impact: HIGH
impactDescription: one place decides who may do what
tags: arch, application, policies, authorization
---

## Business Rules and Ownership Checks Live in the Application Layer

**Impact: HIGH (one place decides who may do what)**

Use cases, policies and actions in `app/Application` own business rules, ownership and authorization. A Policy answers the question (`ArticlePolicy::canDelete`); the service applies it and returns a typed failure. Controllers, Resources and mappers never decide it.

**Incorrect (legacy `ArticleController::update`, retired in `635d079c`):**

```php
$article = Article::find($id);

if (! $article || $article->user_id != auth()->user()->id) {
    return response()->json([
        'success' => false,
        'message' => 'article doesnt exist or does not belong to the user',
    ]);
}
```

**Correct (`ArticlePolicy` plus `ArticleService`):**

```php
public function canDelete(?AuthenticatedUser $authenticatedUser, Article $article): bool
{
    if ($authenticatedUser === null) {
        return false;
    }
    if ($authenticatedUser->isAdmin) {
        return true;
    }
    return $authenticatedUser->id->equals($article->getAuthorId());
}

// ArticleService
if (! $this->articlePolicy->canView($authenticatedUser, $article)) {
    return Result::failure(ArticleErrors::accessDenied($articleUid->value()));
}
```

Source: `processor-api/AGENTS.md` §3, `.ai/guidelines/backend-v1-architecture.md`.
