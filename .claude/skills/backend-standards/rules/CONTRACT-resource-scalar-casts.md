---
title: Cast Public Scalars Explicitly in Resource toArray()
impact: MEDIUM
impactDescription: the documented wire type matches the runtime type
tags: contract, resources, openapi, types
---

## Cast Public Scalars Explicitly in Resource toArray()

**Impact: MEDIUM (the documented wire type matches the runtime type)**

Cast every public scalar in `toArray()`: `(int)`, `(bool)`, `(string)`. Scramble infers the schema from the expression; without a cast it can document a boolean or a nullable value as the wrong type, and the generated client inherits the mistake.

**Incorrect (`6fe1b436^:WordResource`):**

```php
'word_type' => $word->getRawWordType() ?? '',
'word_k_ele' => $word->getRawWritingElements() ?? '',
'furigana_r_ele' => $word->getRawReadingElements() ?? '',
```

**Correct (`WordResource`):**

```php
'word_type' => (string) ($word->getRawWordType() ?? ''),
'word_k_ele' => (string) ($word->getRawWritingElements() ?? ''),
'furigana_r_ele' => (string) ($word->getRawReadingElements() ?? ''),
```

Source: `processor-api/AGENTS.md` §5.
