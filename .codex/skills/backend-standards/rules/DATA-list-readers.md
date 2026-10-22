---
title: Filtered and Paginated List Reads Go Through a *ReaderInterface
impact: HIGH
impactDescription: list queries can change or move to a search engine without touching use cases
tags: data, readers, pagination, lists
---

## Filtered and Paginated List Reads Go Through a *ReaderInterface

**Impact: HIGH (list queries can change or move to a search engine without touching use cases)**

A list read with filters, facets or pagination is a `*ReaderInterface` in `Application/{Module}/Interfaces/Readers`, implemented in Infrastructure. It returns domain models and scalar pagination (`ArticlePageDTO`), never a Laravel paginator. Identity reads and writes stay on the repository. For a plain ordered query with no filters, the reader is optional (ADR 0001). Known debt: `CatalogueRepositoryInterface::findByCriteria` still returns a paginator-wrapping `Catalogues`, and `LikeRepositoryInterface::findAllByFilter` returns a `Paginator`.

**Incorrect (`e4d91510^:ArticleRepositoryInterface`, replaced in #282):**

```php
public function findByCriteria(ArticleCriteriaDTO $criteria): Articles;

// Domain/Articles/Models/Articles
public static function fromEloquentPaginator(LengthAwarePaginator $paginator): self
public function getPaginator(): LengthAwarePaginator
```

**Correct (`ArticleListReaderInterface`):**

```php
interface ArticleListReaderInterface
{
    public function search(
        ArticleQueryCriteria $criteria,
        ArticleVisibilityScope $scope,
        ArticleListIncludes $includes,
    ): ArticlePageDTO;

    /** @return array<int, ArticleFacetDTO> */
    public function facets(ArticleQueryCriteria $criteria, ArticleVisibilityScope $scope): array;
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`, `docs/adr/0001-read-enrichment-lives-beside-the-entity.md`.
