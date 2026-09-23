# Community and Engagement — Mutation Points

> **Status:** Mutation inventory; every mutation on v1
> **Last reviewed:** 2026-09-19
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; Post retirement (RET-POST-01) reflected 2026-09-19
> **Audience:** Backend, frontend, QA, and migration contributors

## v1 Mutations

| Mutation | Input identity | Authorization | Effect | State |
|---|---|---|---|---|
| Create comment | Entity type + numeric ID + UUID | Authenticated route | Persists comment for supported entity. | Verified current |
| Create reply | Comment create plus parent comment ID | Authenticated route | Persists child comment relationship. | Contract present; reply read breadth incomplete |
| Toggle like | Object type + numeric real-object ID | Authenticated route | Creates or removes viewer like. | Verified current |
| Record view | Feature service/action identity | Viewer-aware feature flow | Persists view state/count. | Verified in migrated feature services |
| Record download | PDF/export action identity | Authorized export flow | Persists download state/count. | Verified in PDF application tests |
| Create/sync hashtags | Feature create/update DTO | Feature authorization | Adds or replaces entity hashtag relationships. | Verified for articles/catalogues |

## Formerly Legacy Mutations

Every resource-specific legacy mutation route is retired. The `routes/api.php` ledger comments record each replacement per family.

| Mutation | Current owner | State |
|---|---|---|
| Update/delete comment | `PUT/DELETE v1/comments/{uuid}`, owner for update, owner-or-admin for delete; legacy routes retired across RET-ART-01, RET-CAT-01, RET-SEN-01 and RET-POST-01. | Reached. |
| Like/unlike comment | `POST v1/like-instance`, one idempotent toggle; the legacy article/list/sentence/post controller pairs are gone. | Reached. |
| Create/update/delete post | `POST v1/posts`, `PUT/DELETE v1/posts/{uuid}` in `App\Http\v1\Community\Posts`; the legacy `PostController` retired in RET-POST-01. | Reached. |
| Lock/unlock post | `PUT v1/posts/{uuid}/lock`, admin-only, explicit boolean state; both legacy toggle paths retired in RET-POST-01. | Reached. |
| Sentence comment writes | Generic v1 comment contract (`POST v1/comments` with `entity_type=sentence`); the legacy JapaneseDataController routes retired in RET-SEN-01. | Reached. |

## Side Effects and Cache Behavior

- Comment creation should invalidate the owning resource's comment query and engagement summary.
- Like toggles should update viewer state and counts without inventing a second query-key family.
- Hashtag changes can affect discovery filters as well as detail presentation.
- Entity deletion must clean up related engagement records where the owning feature service defines that transaction.

## Constraints

- The object-template enum is the public type contract for shared writes.
- Legacy numeric mappings remain a persistence compatibility detail.
- Route authentication and application authorization must both be reviewed during migration.
- A mutation counts as complete only when controller, service, policy, response, and tests exist; the retirement tests under `tests/Feature/Routes/` keep the legacy routes from coming back.
