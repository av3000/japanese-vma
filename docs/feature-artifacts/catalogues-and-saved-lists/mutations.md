# Catalogues and Saved Lists — Mutation Points

> **Status:** Baseline mutation inventory
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Backend, frontend, QA, and migration contributors

## Catalogue Mutations

| Mutation | Authorization | Primary effect | Secondary effects | State |
|---|---|---|---|---|
| Create catalogue | Authenticated user | Persists a typed owned catalogue | Creates hashtags when supplied | Verified current v1 |
| Update catalogue | Authorized owner | Applies title, type, publicity, or tag changes | Synchronizes hashtags and list/detail visibility | Verified current v1 |
| Delete catalogue | Authorized owner | Deletes catalogue | Deletes items and related engagement/download/tag records in the service transaction | Verified current v1 |
| Toggle publicity | Authorized owner through partial update | Changes Private/Public state | Changes public discovery and detail access | Verified current v1 |

## Item Mutations

| Mutation | Authorization | Validation | Effect |
|---|---|---|---|
| Add item | Authenticated owner | Catalogue exists, item type matches, membership absent | Persists catalogue-item association. |
| Remove item | Authenticated owner | Catalogue exists, membership present | Deletes catalogue-item association. |
| Mark learned | Authenticated user through known-catalogue behavior | Item and known catalogue type align | Adds item to the relevant learned-state catalogue. |
| Unmark learned | Authenticated user through known-catalogue behavior | Membership exists | Removes item from learned-state catalogue. |

## Read-Triggered and Output Effects

| Operation | Effect | State |
|---|---|---|
| Open catalogue detail | Can record a view for the viewer/entity. | Verified current service behavior |
| Export catalogue PDF | Renders supported study data and can record a download. | Verified for kanji/word v1 |
| Like catalogue | Mutates shared engagement state. | Mixed v1 frontend/legacy compatibility |
| Create catalogue comment | Persists generic v1 comment for the catalogue entity tuple. | Verified current v1 |

## Client State Effects

Add/remove operations update catalogue-for-item state and invalidate affected catalogue detail/list queries. Delete clears detail and list cache state before navigation. Compatibility adapters must not create a second cache-key family for the same v1 resource.

## Constraints

- Catalogue UUID is the v1 mutation identity.
- Item identity is currently numeric at the item mutation boundary.
- Catalogue type owns compatible item kind.
- Empty partial updates are rejected.
- Legacy saved-list endpoints should remain isolated and removable.
