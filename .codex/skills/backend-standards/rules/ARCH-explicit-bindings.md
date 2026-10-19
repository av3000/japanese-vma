---
title: Bind Interfaces Explicitly in the Providers
impact: LOW
impactDescription: one place shows which adapter serves each port
tags: arch, providers, dependency-injection
---

## Bind Interfaces Explicitly in the Providers

**Impact: LOW (one place shows which adapter serves each port)**

Every service, repository and reader interface is bound to its implementation in a provider: `*ServiceInterface` in `ArticlesServiceProvider`, repositories and readers in `RepositoryServiceProvider`. Do not construct an adapter with `new` at the call site or resolve it through a helper.

**Incorrect (hypothetical, no instance remains in the repo: an adapter built inline, so swapping it means editing every caller):**

```php
public function __construct()
{
    $this->articleListReader = new DatabaseArticleListReader();
}
```

**Correct (`RepositoryServiceProvider`):**

```php
// Article list read ports. Swapping in a search-engine reader later means
// rebinding ArticleListReaderInterface here and nothing else.
$this->app->singleton(
    ArticleListReaderInterface::class,
    DatabaseArticleListReader::class
);
```

Source: `.ai/guidelines/backend-v1-architecture.md` (guardrails).
