---
title: A Reusable or Side-Effectful Step Is a Focused Action
impact: MEDIUM
impactDescription: services keep one reason to change
tags: arch, actions, services, side-effects
---

## A Reusable or Side-Effectful Step Is a Focused Action

**Impact: MEDIUM (services keep one reason to change)**

A service may orchestrate several collaborators for one use case. When a step is reused, has its own failure handling, talks to an external mechanism, or is a side effect such as recording a download, move it into a focused action (or an adapter behind a port) and let the service call it. Split a service when its reasons to change diverge: rendering, authorizing, fetching metadata and tracking analytics are four reasons.

**Incorrect (`c691631a^:app/Application/Pdf/PdfExportService.php`: render and download tracking inline in one generic service):**

```php
try {
    $renderResult = $this->pdfRenderer->render($preparation->document);
} catch (Throwable $exception) {
    return Result::failure(PdfExportErrors::renderFailed($exception->getMessage()));
}

try {
    $this->downloadRepository->create(new DownloadCreateDTO(
        userId: $request->viewer->id,
        templateId: $preparation->downloadTarget->objectType->getLegacyId(),
        realObjectId: $preparation->downloadTarget->entityId,
    ));
} catch (Throwable $exception) {
    Log::warning('PDF export download tracking failed', [/* ... */]);
}
```

**Correct (`RecordDownloadAction`, called by `ArticlePdfExportService` after the policy check and the render):**

```php
public function record(UserId $viewerId, ObjectTemplateType $objectType, int $entityId, array $context = []): void
{
    try {
        $this->downloadRepository->create(new DownloadCreateDTO(
            userId: $viewerId->value(),
            templateId: $objectType->getLegacyId(),
            realObjectId: $entityId,
        ));
    } catch (Throwable $exception) {
        Log::warning('PDF download tracking failed', array_merge($context, [/* ... */]));
    }
}
```

Source: `.ai/guidelines/backend-v1-architecture.md` (guardrails), the retired `backend-architecture-boundaries` skill.
