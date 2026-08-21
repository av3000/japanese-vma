# Community and Engagement — Mutation Points

> **Status:** Baseline mutation inventory
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
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

## Legacy Mutations

| Mutation | Current owner | Target direction |
|---|---|---|
| Update/delete comment | Resource-specific legacy routes | Generic authorized v1 mutation contract. |
| Like/unlike comment | Legacy article/list/sentence/post controllers | Shared typed engagement boundary. |
| Create/update/delete post | Legacy PostController | Feature-local v1 post read/write modules. |
| Lock/unlock post | Legacy admin/moderation route | Authorized v1 moderation contract. |
| Sentence comment writes | Legacy JapaneseDataController | Generic comment contract after target identity decisions. |

## Side Effects and Cache Behavior

- Comment creation should invalidate the owning resource's comment query and engagement summary.
- Like toggles should update viewer state and counts without inventing a second query-key family.
- Hashtag changes can affect discovery filters as well as detail presentation.
- Entity deletion must clean up related engagement records where the owning feature service defines that transaction.

## Constraints

- The object-template enum is the public type contract for shared writes.
- Legacy numeric mappings remain a persistence compatibility detail.
- Route authentication and application authorization must both be reviewed during migration.
- Update/delete behavior is not complete in v1 until controller, service, policy, response, and tests exist.
