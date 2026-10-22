---
title: Pin Non-Obvious Schema in an OpenApiTest
impact: MEDIUM
impactDescription: a Resource refactor cannot silently change the generated client
tags: test, openapi, api-json, scramble
---

## Pin Non-Obvious Schema in an OpenApiTest

**Impact: MEDIUM (a Resource refactor cannot silently change the generated client)**

After changing a Resource or a `#[Response]`, check the component's `properties` and `required` in `api.json`, and pin anything non-obvious (a cast type, an optional include, a shared enum `$ref`) in a `tests/Unit/...OpenApiTest` that reads `api.json`. Reference: `tests/Unit/JapaneseMaterial/DictionaryResponsesOpenApiTest.php`.

**Incorrect (`6fe1b436^:WordResource`: no cast and no test reading the component, so the wrong schema type reached the generated client unnoticed until UI-DICT-06):**

```php
'word_types' => $word->getWordTypes(),
'word_type' => $word->getRawWordType() ?? '',
// no OpenApiTest asserted WordResource's properties
```

**Correct (`DictionaryResponsesOpenApiTest`):**

```php
public function test_word_and_radical_components_keep_runtime_scalar_types(): void
{
    $word = $this->schema('WordResource')['properties'];
    $this->assertSame('integer', $word['id']['type']);
    $this->assertSame('string', $word['word_type']['type']);
}

public function test_optional_includes_are_documented_as_optional(): void
{
    $radical = $this->schema('RadicalResource');
    $this->assertNotContains('kanjis', $radical['required']);
}
```

Source: `processor-api/AGENTS.md` §5.
