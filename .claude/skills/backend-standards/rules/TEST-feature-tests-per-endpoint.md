---
title: Endpoint Changes Get Feature Tests for Success, Failure and Edges
impact: HIGH
impactDescription: the contract each status code promises is pinned
tags: test, feature-tests, phpunit, endpoints
---

## Endpoint Changes Get Feature Tests for Success, Failure and Edges

**Impact: HIGH (the contract each status code promises is pinned)**

Every behaviour change is tested. For an endpoint, write PHPUnit feature tests (`RefreshDatabase`, `Passport::actingAs(...)`, `$this->json(...)`) for the success path and each failure it can return: 401, 403, 404 and 422, plus the edge cases the change introduces. Name each test after the behaviour.

**Incorrect (hypothetical: only the happy path; the owner check, unknown uuid and empty payload are untested):**

```php
public function test_update_works(): void
{
    Passport::actingAs($owner, ['*'], 'api');
    $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", ['title' => 'New'])->assertOk();
}
```

**Correct (`tests/Feature/Catalogues/StoreUpdateCatalogueTest.php`):**

```php
public function test_update_forbids_non_owner(): void
{
    $owner = $this->createUser();
    $otherUser = $this->createUser();
    $catalogue = $this->createCatalogue($owner);
    Passport::actingAs($otherUser, ['*'], 'api');

    $this->json('PUT', "/api/v1/catalogues/{$catalogue->uuid}", [
        'title' => 'Unauthorized Update',
    ])->assertStatus(403);
}

// also: test_update_allows_owner_to_partially_update_and_replace_hashtags,
// test_update_empty_payload_returns_validation_problem (422),
// test_update_returns_not_found_for_unknown_uuid (404)
```

Source: `.ai/guidelines/enforce-tests.blade.php`, `processor-api/AGENTS.md` §6.
