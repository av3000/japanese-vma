---
title: List Shapes Use the Sibling Names
impact: MEDIUM
impactDescription: every module's list pipeline reads the same
tags: data, naming, lists, dto
---

## List Shapes Use the Sibling Names

**Impact: MEDIUM (every module's list pipeline reads the same)**

| Role | Name | Lives in | Example |
| --- | --- | --- | --- |
| List input | `*QueryCriteria` | `Domain/{Module}/Queries` | `ArticleQueryCriteria` |
| Optional-enrichment flags | `*Includes` | `Domain/{Module}/DTOs` | `ArticleListIncludes` |
| Raw reader output | `*PageDTO` | `Domain/{Module}/DTOs` | `ArticlePageDTO` |
| Enriched envelope | `*ListResultDTO` | `Domain/{Module}/DTOs` | `ArticleListResultDTO` |
| Sort | `*SortCriteria` | `Domain/{Module}/ValueObjects` | `ArticleSortCriteria` |
| Per-read enrichment | `*ListItemDTO` | `Domain/{Module}/DTOs` | `CommentListItemDTO` |

Do not introduce `*Query`, `*Projection`, `*Page` or `*ReadResult` for these roles.

**Incorrect (`eeacefba^`, renamed before #282 merged; also put the DTOs in Application):**

```php
use App\Application\Articles\DTOs\ArticleListProjection;
use App\Application\Articles\DTOs\ArticleListQuery;
use App\Application\Articles\DTOs\ArticleListReadResult;

public function search(ArticleListQuery $query, ArticleVisibilityScope $scope, ArticleListProjection $projection): ArticleListReadResult;
```

**Correct:**

```php
public function search(
    ArticleQueryCriteria $criteria,
    ArticleVisibilityScope $scope,
    ArticleListIncludes $includes,
): ArticlePageDTO;
```

Source: `.ai/guidelines/backend-v1-architecture.md`.
