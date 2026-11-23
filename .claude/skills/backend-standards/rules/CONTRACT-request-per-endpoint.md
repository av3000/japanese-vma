---
title: Each v1 Endpoint Validates Through Its Own Request Class
impact: MEDIUM
impactDescription: invalid input is rejected with one 422 shape before any use case runs
tags: contract, requests, validation
---

## Each v1 Endpoint Validates Through Its Own Request Class

**Impact: MEDIUM (invalid input is rejected with one 422 shape before any use case runs)**

Every v1 action takes a dedicated `FormRequest` and reads `$request->validated()`. No `$request->validate([...])`, `Validator::make` or raw `$request->get()` in a controller, and nothing unvalidated reaches a service or the database. The Request is also the documented contract: Scramble reads its rules into `api.json`.

**Incorrect (`eb0d8d0c^:app/Http/Controllers/CustomListController.php`: no validation, raw input straight into an insert):**

```php
public function addToList(Request $request, $id)
{
    $list = CustomList::find($id);
    $newObjectId = $request->get('real_object_id');
    $row = ['real_object_id' => $newObjectId, 'listtype_id' => $list->type, 'list_id' => $id];
    DB::table('customlist_object')->insert($row);
```

**Correct (`CatalogueController::forItem` with `IndexCataloguesForItemRequest`):**

```php
public function forItem(IndexCataloguesForItemRequest $request): JsonResponse|JsonResource
{
    $validated = $request->validated();
    $cataloguesForItem = $this->catalogueService->getCataloguesForItem(
        (int) $validated['item_id'],
        $validated['types'] ?? [],
        $validated['search'] ?? null,
        $this->requiredAuthenticatedUser(),
    );

    return new CatalogueListForItemResource($cataloguesForItem);
}
```

Source: `processor-api/AGENTS.md` §4, `.ai/guidelines/backend-response-and-errors.md`.
