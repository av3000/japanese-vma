---
title: Implementation Notes Go Above the return, Not on Array Keys
impact: LOW
impactDescription: internal notes do not leak into the public API description
tags: contract, resources, scramble, openapi
---

## Implementation Notes Go Above the return, Not on Array Keys

**Impact: LOW (internal notes do not leak into the public API description)**

Scramble turns a comment on an array key inside `toArray()` into that property's public `description` in `api.json`, and from there into the generated client. Put implementation notes above the `return`. A `/** @var ... */` type hint on a key is fine: it is a type, not prose.

**Incorrect (`CatalogueDetailResource`, still in the code: the TODO is now the public description of `items` in `api.json`):**

```php
'jlpt_levels' => CatalogueResource::jlptLevels($detail->jlptLevels),
// TODO: add generic items type as 'InstanceItem[]' that would be a list of kanji, words, sentences, radicals or articals type.
'items' => $detail->items,
```

```json
"description": "TODO: add generic items type as 'InstanceItem[]' that would be a list of kanji, words, sentences, radicals or articals type."
```

**Correct (`RelatedArticleSummaryResource`):**

```php
// Explicit casts: Scramble cannot follow `$item->article`, and without them the
// component documents every scalar as `string` and `hashtags` as untyped.
return [
    'id' => (int) $item->article->getIdValue(),
    'uuid' => (string) $item->article->getUid()->value(),
    // ...
];
```

Source: `processor-api/AGENTS.md` §5.
