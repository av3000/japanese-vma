---
title: Staff Writes Go Through the Module's Moderation Service, Called Only From the Panel
impact: HIGH
impactDescription: one entry point and one activity row per staff action
tags: arch, admin, filament, moderation, policies, activity-log
---

## Staff Writes Go Through the Module's Moderation Service, Called Only From the Panel

**Impact: HIGH (one entry point and one activity row per staff action)**

**Enforced by `tests/Unit/Architecture/AdminBoundaryTest`.**

The website and the admin panel are two surfaces with separate rules:

- **Website policies** (`ArticlePolicy`, `CataloguePolicy`, `CommentPolicy`, `PostPolicy`, `SentencePolicy`) govern authors and readers and grant staff nothing.
- **Panel policies** (`app/Policies/*AdminPolicy`, `UserPolicy`, `RolePolicy`) map to the panel permission catalogue in `config/permission-catalog.php`. They decide what the panel shows.
- **Staff writes** for a module live in `Application/{Module}/Services/{Module}ModerationService(Interface)` with `Policies/{Module}ModerationPolicy`. They are called only from `app/Filament`, check their policy before loading anything, and write exactly one activity row in the same transaction as the change.
- **Panel-only use cases** that are not moderation (hashtag management, processing requeue, account administration, the worker check) check their panel permission in the application layer through `ActorPermissionCheckerInterface`. Panel visibility is UX, never the security boundary.

`AdminBoundaryTest` fails the build when website code (`app/Http`, `app/Console`, `app/Jobs` and the non-moderation parts of `app/Application`) reaches moderation or panel-only code, when `app/Filament` acts through a website write service or policy, or when a website policy checks for staff (`isAdmin`, `hasRole`, `UserRole::ADMIN` and similar). It reads resolved class references through `tests/Unit/Architecture/Support/ClassReferenceScanner`, so comments never count and aliased or grouped imports always do. A new panel-only use case that is not named `*Moderation*` goes into `AdminBoundaryRules::PANEL_ONLY`. The `KNOWN_VIOLATIONS` list only shrinks: each entry names its rule and the issue that removes it (#572–#574), and an entry that no longer violates fails the test until it is deleted.

**Incorrect (`ArticleController::setStatus`, a known violation until #572: the website reaches a moderation service):**

```php
public function setStatus(string $uuid, UpdateArticleStatusRequest $request): JsonResponse|JsonResource
{
    $result = $this->articleModerationService->updateStatus(
        EntityId::from($uuid),
        ArticleStatus::from((int) $request->validated('status')),
        $this->requiredAuthenticatedUser(),
    );
```

**Correct (`PostModerationActions`: the panel calls the module's moderation service with the panel actor):**

```php
->visible(static fn (): bool => self::canModerate())
->action(static function (Post $record, Action $action): void {
    $result = app(PostModerationServiceInterface::class)->remove(
        new EntityId((string) $record->uuid),
        PanelActor::current(),
    );
```

Source: epic #179 (ADMIN-PANEL) rules 2 and 3, review #595.
