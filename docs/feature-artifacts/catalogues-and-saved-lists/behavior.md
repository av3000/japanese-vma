# Catalogues and Saved Lists — Behavior

> **Status:** Baseline; v1 behavior and compatibility seams separated
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Frontend, backend, QA, and product-minded contributors

## Reads and Visibility

- Public catalogue lists expose catalogues according to filters and visibility rules.
- Catalogue detail resolves by UUID and denies private content to unauthorized viewers.
- Detail can include items, engagement statistics, hashtags, comments, and viewer-specific state where supported.
- Reading a detail can record a view through the engagement boundary.
- Authenticated for-item reads return relevant owned catalogues and whether each contains the item.

## Creation and Update

Custom catalogue creation requires a title and type. Description, publicity, and tags are optional within validation rules. Custom types currently accepted by the v1 request are radicals, kanji, words, sentences, and articles.

Updates are partial but must contain at least one recognized field. Only authorized owners can change title, type, publicity, or tags.

## Item Membership

Before adding an item, the service verifies:

1. the catalogue exists;
2. the user owns or may mutate it;
3. the item is valid for the catalogue type;
4. the item is not already present.

Removal likewise verifies ownership and existing membership. Duplicate adds and removal of absent items return typed failures rather than silently succeeding.

## Delete Behavior

Catalogue deletion is owner-authorized and removes catalogue items plus associated view, download, like, comment, hashtag, and catalogue persistence records through the application service transaction.

## PDF Behavior

v1 supports kanji and word catalogue exports through the shared PDF renderer boundary. Radical and sentence PDF routes remain legacy behavior and are not v1-complete.

## Frontend Behavior

- Modern catalogue list/detail/form routes use generated clients and React Query.
- The for-item module owns generated reads/writes, state derivation, and optimistic update helpers.
- Successful mutations invalidate or update catalogue list/detail/picker caches at their owning boundary.
- Legacy `/lists`, `/newlist`, `/list/:id`, and `/list/edit/:id` navigation routes redirect into catalogue-oriented screens.

## Failure Behavior

- validation failure for invalid type, title, or empty update;
- unauthenticated mutation;
- forbidden access or mutation by a non-owner;
- catalogue not found;
- invalid item for catalogue type;
- duplicate item;
- item not found during removal;
- unsupported PDF export kind;
- legacy numeric identity cannot be resolved.

## Evidence

- `processor-api/tests/Feature/Catalogues/`
- `processor-api/app/Application/Catalogues/Services/CatalogueService.php`
- `client/src/api/catalogues/`
- `client/src/routes/CatalogueDetails/`
