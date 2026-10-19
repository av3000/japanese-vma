---
title: Domain Never Imports Application, Infrastructure or Http
impact: CRITICAL
impactDescription: dependencies point inward; the domain stays usable from every layer
tags: arch, domain, dependency-direction
---

## Domain Never Imports Application, Infrastructure or Http

**Impact: CRITICAL (dependencies point inward; the domain stays usable from every layer)**

**Enforced by `tests/Unit/Architecture/DomainLayerDependencyTest`**, which fails on any line under `app/Domain` that mentions `App\Application\`, `App\Infrastructure\` or `App\Http\` (comments and strings included; no allowlist). Reviewers skip this rule; the test reports it.

`app/Domain` may depend on `App\Shared` and on itself. When a domain shape needs data that lives in an outer layer, give it a domain type and let the outer layer map into it.

**Incorrect (`1626be9e`, removed in #282: a domain DTO typed an Eloquent model):**

```php
namespace App\Domain\Articles\DTOs;

use App\Infrastructure\Persistence\Models\LastOperationState;

readonly class ArticleDetailResultDTO
{
    public function __construct(
        public Article $article,
        // ...
        public ?LastOperationState $lastOperation,
    ) {
    }
}
```

**Correct (the domain owns the shape; Infrastructure maps into it):**

```php
namespace App\Domain\Articles\DTOs;

readonly class ArticleDetailResultDTO
{
    public function __construct(
        public Article $article,
        // ...
        public ?ArticleProcessingStateDTO $processingState,
    ) {
    }
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`, `processor-api/AGENTS.md` §3.
