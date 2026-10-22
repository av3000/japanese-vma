---
title: Other Modules Call a *ServiceInterface That Returns Result
impact: HIGH
impactDescription: modules depend on a stable contract, not each other's internals
tags: arch, modules, services, result
---

## Other Modules Call a *ServiceInterface That Returns Result

**Impact: HIGH (modules depend on a stable contract, not each other's internals)**

When one module needs another, it injects that module's `*ServiceInterface` and gets a `Result` back. Actions are module-internal steps: they are not injected across module boundaries. Known debt: several services still inject Engagement actions directly (`IncrementViewAction`, `RecordDownloadAction`, `LoadEntityStatsAction` in `ArticleService`, `ArticlePdfExportService`, `CatalogueService` and others); do not add more.

**Incorrect (`app/Application/Articles/Services/ArticleService.php`: another module's action injected directly):**

```php
use App\Application\Engagement\Actions\IncrementViewAction;

public function __construct(
    // ...
    private IncrementViewAction $incrementViewAction,
) {
}
```

**Correct (`StudySessionService` calls Catalogues through its interface):**

```php
use App\Application\Catalogues\Services\CatalogueServiceInterface;

public function __construct(
    private readonly CatalogueServiceInterface $catalogueService,
) {
}

$catalogueResult = $this->catalogueService->getViewableCatalogue($dto->catalogueUuid, $user);

if ($catalogueResult->isFailure()) {
    return $catalogueResult;
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`.
