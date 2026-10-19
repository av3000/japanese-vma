---
title: Comment Writes Use the Generic Enum Payload
impact: MEDIUM
impactDescription: one write endpoint and one entity-type vocabulary for every parent
tags: contract, comments, likes, enums
---

## Comment Writes Use the Generic Enum Payload

**Impact: MEDIUM (one write endpoint and one entity-type vocabulary for every parent)**

`POST /api/v1/comments` takes `{ entity_type: ObjectTemplateType, entity_id, entity_uuid, content, parent_comment_id? }`, validated in `StoreCommentRequest` with `Rule::enum(ObjectTemplateType::class)`. Do not reintroduce string names (`'article'`, `'catalogue'`), title lookups or per-parent comment endpoints; responses return the same enum the request takes. The like toggle is the deliberate exception that proves a target before writing: `LikeTargetRepositoryInterface::isVisibleTo` checks existence and visibility per target type, so a like never lands on a missing or private target, and the 404 does not tell the two apart.

**Incorrect (`97e15126^:CommentMapper`: the entity type is a free string resolved by a title lookup with a magic fallback):**

```php
private static function getTemplateIdFromEntityType(string $entityType): int
{
    static $typeCache = [];
    if (! isset($typeCache[$entityType])) {
        $template = ObjectTemplate::where('title', $entityType)->first();
        $typeCache[$entityType] = $template ? $template->id : 1; // fallback
    }
    return $typeCache[$entityType];
}
```

**Correct (`StoreCommentRequest`):**

```php
public function rules(): array
{
    return [
        'entity_type' => ['required', Rule::enum(ObjectTemplateType::class)],
        'entity_id' => ['required', 'integer', 'min:1'],
        'entity_uuid' => 'required|string|uuid',
        'content' => 'required|string|min:2|max:1000',
        'parent_comment_id' => 'sometimes|integer|min:1',
    ];
}
```

Source: `processor-api/AGENTS.md` §2 and §4.
