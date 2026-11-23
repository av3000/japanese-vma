---
title: Partial Updates Validate Optional Fields and Reject an Empty Payload
impact: MEDIUM
impactDescription: no silent no-op updates, no unvalidated field writes
tags: contract, requests, validation, update
---

## Partial Updates Validate Optional Fields and Reject an Empty Payload

**Impact: MEDIUM (no silent no-op updates, no unvalidated field writes)**

A partial update validates every field with `sometimes` and rejects a payload with no updatable field as a 422 validation problem, inside the Request (`withValidator` after-hook), so the controller never sees it. Same pattern in `UpdateCatalogueRequest`, `UpdateArticleRequest` and `UpdatePostRequest`.

**Incorrect (`eb0d8d0c^:CustomListController::update`: nothing validated, an empty body "succeeds"):**

```php
if (isset($request->title)) { $list->title = $request->get('title'); }
if (isset($request->publicity)) { $list->publicity = $request->get('publicity'); }
if (isset($request->type)) { $list->type = $request->get('type'); }
$list->update();

return response()->json(['success' => true, 'updatedList' => $list]);
```

**Correct (`UpdateCatalogueRequest`):**

```php
public function rules(): array
{
    return [
        'title' => 'sometimes|string|min:2|max:255',
        'type' => 'sometimes|integer|in:5,6,7,8,9',
        'publicity' => 'sometimes|boolean',
        'tags' => 'sometimes|nullable|string',
    ];
}

public function withValidator(Validator $validator): void
{
    $validator->after(function () use ($validator): void {
        if ($validator->errors()->isNotEmpty() || $this->hasAnyUpdateableFields()) {
            return;
        }
        throw new HttpResponseException(
            TypedResults::validationProblem(['fields' => [self::EMPTY_UPDATE_MESSAGE]], 'No fields to update'),
        );
    });
}
```

Source: `processor-api/AGENTS.md` §4.
