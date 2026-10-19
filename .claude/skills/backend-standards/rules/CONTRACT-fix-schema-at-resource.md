---
title: When a Component Is Wrong in api.json, Fix the Resource
impact: MEDIUM
impactDescription: the schema stays derived from the code that produces the response
tags: contract, resources, openapi, scramble
---

## When a Component Is Wrong in api.json, Fix the Resource

**Impact: MEDIUM (the schema stays derived from the code that produces the response)**

Fix a wrong named component at the Resource, not by overriding the annotation:

- every scalar typed `string`: add a class-level `@property Domain\Model $resource`, or explicit casts when the value comes through a nested object;
- `array_map` items untyped: add `/** @var array<int, XResource> */` above `'items' => array_map(...)`;
- an optional include documented as required: build it with `'key' => $this->when($condition, fn () => ...)`, not a conditional `$payload['key'] = ...`;
- a detail Resource that extends another: `$this->merge((new XResource(...))->toArray($request))`. Merging the Resource object itself drops its fields from the schema.

**Incorrect (`6fe1b436^:RadicalResource`: a conditional assignment, so `kanjis` is documented as required and untyped):**

```php
$payload = ['id' => $radical->getId(), 'hiragana' => $radical->getHiragana()];
if ($this->includeKanjis) {
    $payload['kanjis'] = array_map(
        fn (DomainKanji $kanji): KanjiResource => new KanjiResource($kanji),
        $radical->getKanjis(),
    );
}
return $payload;
```

**Correct (`WordResource` and `WordDetailResource`):**

```php
/**
 * @property Word $resource
 */
class WordResource extends JsonResource

// WordDetailResource::toArray()
return [
    $this->merge((new WordResource($this->resource->word))->toArray($request)),
    /** @var array<int, KanjiResource> */
    'kanjis' => $this->when(
        $kanjis !== null,
        static fn (): array => array_map(
            static fn (Kanji $kanji): KanjiResource => new KanjiResource($kanji),
            $kanjis ?? [],
        ),
    ),
];
```

Source: `processor-api/AGENTS.md` §5.
