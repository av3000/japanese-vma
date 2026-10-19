---
title: Lists Use the Shared Envelope, Pagination and Include Flags
impact: MEDIUM
impactDescription: one list shape across modules, one generated client pattern
tags: result, pagination, envelope, includes
---

## Lists Use the Shared Envelope, Pagination and Include Flags

**Impact: MEDIUM (one list shape across modules, one generated client pattern)**

A v1 list returns `items` and `pagination` (the shared `PaginationResource`: `page`, `per_page`, `total`, `last_page`, `has_more`), plus `facets` and `applied` where the module has them, with no `data` wrapper. Optional enrichment is switched with `include_*` request flags mapped once into a `*Includes` object. Copy the nearest module rather than inventing keys.

**Incorrect (legacy article index: Laravel's raw paginator JSON under an endpoint-specific key):**

```php
$articles = Article::query()->where('publicity', 1)->paginate($perPage);

return response()->json(['success' => true, 'articles' => $articles]);
// { "articles": { "current_page": 1, "data": [...], "last_page": 9, ... } }
```

**Correct (`ArticleListResource` and `ArticleListIncludes`):**

```php
return [
    /** @var array<int, ArticleResource> */
    'items' => $items,
    /** @var array<int, ArticleFacetResource> */
    'facets' => $facets,
    'applied' => new ArticleAppliedCriteriaResource($this->resource->criteria),
    'pagination' => new PaginationResource($this->resource->pagination->toArray()),
];

public static function fromValidated(array $validated): self
{
    return new self(
        includeStats: (bool) ($validated['include_stats_counts'] ?? true),
        includeHashtags: (bool) ($validated['include_hashtags'] ?? true),
        // ...
    );
}
```

Source: `.ai/guidelines/backend-response-and-errors.md`, `processor-api/AGENTS.md` §5.
