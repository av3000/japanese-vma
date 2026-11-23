---
title: No HTTP, Eloquent or Query Builders in the Domain
impact: HIGH
impactDescription: domain logic runs and tests without a request or a database
tags: arch, domain, eloquent, framework
---

## No HTTP, Eloquent or Query Builders in the Domain

**Impact: HIGH (domain logic runs and tests without a request or a database)**

`app/Domain` holds domain models, DTOs, value objects, enums and typed errors. It does not receive HTTP requests or build responses, and it does not touch Eloquent models, `DB` or query builders. Queries belong in Infrastructure (`DATA-repositories-hide-persistence`). Known older debt: the `fromEloquentPaginator` helpers on the domain list collections (`Articles`, `Catalogues`, `Kanjis`, `Users`, `Likes`) import `LengthAwarePaginator`; do not copy them.

**Incorrect (`671a4161`: a domain "action" running Eloquent queries):**

```php
namespace App\Domain\Articles\Actions\Retrieval;

use App\Http\Models\{Like, Download, View, Comment, ObjectTemplate};

class LoadArticleStats
{
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $article->likesTotal = $this->getCount(Like::class, $objectTemplateId, $article->id);
    }
}
```

**Correct (`app/Domain/Articles/DTOs/ArticleListItemDTO.php`: only domain types):**

```php
use App\Domain\Articles\Models\Article;
use App\Domain\Articles\Models\ArticleStats;
use App\Domain\Processing\DTOs\ProcessingStateDTO;

final readonly class ArticleListItemDTO
{
    public function __construct(
        public Article $article,
        public ?ArticleStats $stats,
        public array $hashtags,
        public ?ProcessingStateDTO $processingState,
    ) {
    }
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`, `processor-api/AGENTS.md` §3 ("keep HTTP/transport details from leaking into domain entities").
