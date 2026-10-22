---
title: v1 Routes Address Entities by UUID Through EntityId
impact: LOW
impactDescription: one identifier convention per module
tags: contract, identifiers, uuid, value-objects
---

## v1 Routes Address Entities by UUID Through EntityId

**Impact: LOW (one identifier convention per module)**

Follow the identifier convention of the module you touch. v1 routes take the public uuid, the controller wraps it once with `EntityId::from($uuid)`, and services and repositories take the value object (`findByPublicUid(EntityId $uuid)`). Numeric ids stay internal, except where a module's existing contract already uses them (comment and like writes carry `entity_id` beside `entity_uuid`).

**Incorrect (`635d079c^:routes/api.php` and the legacy controller: numeric ids end to end):**

```php
Route::post('article/{id}/togglepublicity', 'ArticleController@togglePublicity');

public function togglePublicity($id)
{
    $article = Article::find($id);
```

**Correct (`ArticleController::setStatus`):**

```php
public function setStatus(string $uuid, UpdateArticleStatusRequest $request): JsonResponse|JsonResource
{
    $result = $this->articleModerationService->updateStatus(
        EntityId::from($uuid),
        ArticleStatus::from((int) $request->validated('status')),
        $this->requiredAuthenticatedUser(),
    );
```

Source: `processor-api/AGENTS.md` §4.
