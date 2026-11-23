---
title: Controllers Coordinate, They Do Not Orchestrate
impact: HIGH
impactDescription: use cases stay testable and reusable without HTTP
tags: arch, controllers, http
---

## Controllers Coordinate, They Do Not Orchestrate

**Impact: HIGH (use cases stay testable and reusable without HTTP)**

A v1 controller takes a validated Request, turns it into DTOs or value objects, calls one application service method, maps a failure with `TypedResults::fromError`, and returns a Resource or a `TypedResults` helper. It does not query, enrich lists, call several services to assemble one response, record side effects or decide authorization.

**Incorrect (`85705d7c`: a v1 controller orchestrating three services and assembling pagination):**

```php
$paginatedArticles = $this->articleService->getArticlesList($listDTO, $viewer);

if ($listDTO->include_stats_counts) {
    $statsMap = $this->engagementService->enhanceArticlesWithStatsCounts($paginatedArticles);
}
if ($listDTO->include_hashtags) {
    $hashtagsMap = $this->hashtagService->getBatchHashtags($entityIdInts, ObjectTemplateType::ARTICLE);
}
foreach ($paginatedArticles->getItems() as $article) {
    $resources[] = new ArticleResource($article, [...], $stats, $hashtags, $lastOperation);
}
return new ArticleListResource(['items' => $resources, 'pagination' => [/* ... */]]);
```

**Correct (`ArticleController::index`):**

```php
public function index(IndexArticleRequest $request): JsonResponse|JsonResource
{
    $validated = $request->validated();

    $result = $this->articleListService->list(
        ArticleQueryCriteria::fromValidated($validated),
        ArticleListIncludes::fromValidated($validated),
        $this->currentUserProvider->currentAuthenticatedUser(),
    );

    if ($result->isFailure()) {
        return TypedResults::fromError($result->getError());
    }

    return new ArticleListResource($result->getData());
}
```

Source: `processor-api/AGENTS.md` §3, `.ai/guidelines/backend-v1-architecture.md`.
