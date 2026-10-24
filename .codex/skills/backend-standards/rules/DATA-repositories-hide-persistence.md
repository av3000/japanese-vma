---
title: Repositories Hide Persistence and Return Domain Shapes
impact: HIGH
impactDescription: Application code never depends on Eloquent or table shapes
tags: data, repositories, eloquent, ports
---

## Repositories Hide Persistence and Return Domain Shapes

**Impact: HIGH (Application code never depends on Eloquent or table shapes)**

A repository interface lives in `Application/{Module}/Interfaces/Repositories` and speaks in domain models, value objects and DTOs. Its implementation in `Infrastructure/Persistence/Repositories` owns Eloquent, eager loading, batching and filtering, and maps rows through a mapper. No interface method returns an Eloquent model, a raw row array or a paginator. Known debt: `ViewRepositoryInterface::findAllByFilter` and `ArticleRepositoryInterface::findByUserId` still return raw arrays; do not add more.

**Incorrect (`app/Application/Engagement/Interfaces/Repositories/ViewRepositoryInterface.php` and its implementation):**

```php
use App\Infrastructure\Persistence\Models\View;
use Illuminate\Database\Eloquent\Collection;

interface ViewRepositoryInterface
{
    public function findAllByFilter(ViewFilterDTO $filters): array;
}

// ViewRepository
public function findAllByFilter(ViewFilterDTO $filter): array
{
    return $this->buildBaseQuery($filter)->get()->toArray();
}
```

**Correct (`CatalogueRepositoryInterface` and `ArticleRepository`):**

```php
public function findByPublicUid(EntityId $uuid): ?Catalogue;

// ArticleRepository::findByPublicUid
$persistenceArticle = $query->first();

return $persistenceArticle
    ? $this->articleMapper->mapToDomain($persistenceArticle, $options)
    : null;
```

Source: `processor-api/AGENTS.md` §3, `.ai/guidelines/backend-v1-architecture.md`.
