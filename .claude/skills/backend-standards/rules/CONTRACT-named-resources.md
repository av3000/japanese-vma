---
title: #[Response] References a Named Resource, Not an Inline Shape
impact: HIGH
impactDescription: one schema component per Resource; stable generated client names
tags: contract, openapi, scramble, resources, orval
---

## #[Response] References a Named Resource, Not an Inline Shape

**Impact: HIGH (one schema component per Resource; stable generated client names)**

When an action returns a Resource, document it by name: `#[Response(type: 'KanjiListResource')]`. An inline `array{...}` shape duplicates the Resource, drifts from it silently, and has no `$ref`, so Orval names the frontend type after the status code (`KanjiIndex200ItemsItem`). Keep `#[Response(type: 'array{...}')]` only for lean ad hoc shapes that have no Resource.

**Incorrect (`6fe1b436^:KanjiController::index`, replaced in UI-DICT-06):**

```php
/**
 * @response array{
 *     items: array<int, array{
 *         id: int, uuid: string, character: string, onyomi: array<int, string>, ...
 *     }>,
 *     pagination: PaginationResource
 * }
 */
#[Response(type: 'array{items: array<int, array{id: int, uuid: string, character: string, ...}>, pagination: PaginationResource}')]
public function index(IndexKanjiRequest $request)
```

**Correct (`KanjiController::index`):**

```php
#[Response(type: 'KanjiListResource')]
public function index(IndexKanjiRequest $request): JsonResponse|JsonResource
```

Source: `processor-api/AGENTS.md` §5.
