---
title: Mappers Translate Shape and Nothing Else
impact: MEDIUM
impactDescription: no hidden queries or policy in the mapping step
tags: data, mappers, persistence
---

## Mappers Translate Shape and Nothing Else

**Impact: MEDIUM (no hidden queries or policy in the mapping step)**

A mapper converts a persistence model into a domain model and back: casts, value objects, parsing stored strings. It runs no queries, applies no visibility or ownership rule, triggers no side effects and invents no fallback values. Add a mapper when the persistence shape should not leak into the domain.

**Incorrect (`97e15126^:CommentMapper`: database lookups and a magic fallback inside the mapper):**

```php
$template = ObjectTemplate::find($templateId);
$templateCache[$templateId] = $template ? $template->title : 'unknown';
// ...
$template = ObjectTemplate::where('title', $entityType)->first();
$typeCache[$entityType] = $template ? $template->id : 1; // fallback
```

**Correct (`WordMapper`):**

```php
public function mapToDomain(PersistenceWord $persistenceWord): DomainWord
{
    return new DomainWord(
        id: (int) $persistenceWord->id,
        uuid: new EntityId((string) $persistenceWord->uuid),
        surface: (string) $persistenceWord->word,
        // ...
        wordTypes: $this->extractStringListFromField($persistenceWord->word_type),
    );
}
```

Source: `.ai/guidelines/backend-v1-architecture.md`, the retired `backend-architecture-boundaries` skill.
