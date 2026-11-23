# Community and Engagement — Behavior

> **Status:** v1 behavior; no legacy community or engagement flow remains
> **Last reviewed:** 2026-09-19
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; Post retirement (RET-POST-01) reflected 2026-09-19
> **Audience:** Frontend, backend, QA, and product-minded contributors

## Comment Reads

- Article comments are read through an article UUID route.
- Catalogue comments are read through a catalogue UUID route.
- The controller resolves UUID to numeric entity ID, then asks the comment service for a paginated resource-specific list.
- Optional viewer identity can enrich comment presentation.
- Reply inclusion is represented in request/resource shapes but is not yet a completed controller behavior.

## Comment Creation

The generic v1 write accepts:

- a recognized entity type;
- a positive numeric entity ID;
- an entity UUID;
- content between the configured length bounds;
- an optional positive parent comment ID.

The route is inside authenticated middleware. The request itself returns true from `authorize`, so route placement is part of the current authentication boundary.

Repository guidance states that the validated entity tuple is the current write contract. A future contract change may strengthen resolution, but new code should not reintroduce ad hoc string-to-type mappings.

## Likes

The v1 instance-like endpoint toggles like state for the authenticated user, numeric object ID, and object type. It returns whether the like now exists and the like resource when present.

The resource-specific legacy like/unlike/checklike routes for articles, lists, sentences, posts, and comments are all retired. The toggle is the only like write; viewer like state is read from the `engagement` block on the owning resource's detail payload.

## Posts

- The list accepts `keyword`, `hashtag`, `topic`, and `sort` (newest or popular by view count) and pages five posts by default.
- The detail route accepts a UUID or, transitionally, a positive legacy numeric id, and always answers with the canonical UUID; the React detail route rewrites a numeric URL to the UUID on first render. An authenticated detail request records exactly one view per viewer; anonymous requests record none.
- Create is owner-scoped and records the author's initial view. Update is owner-only: an admin cannot edit another user's post. Delete is owner-or-admin and removes likes, views, comments, comment likes, and hashtag links in one transaction.
- Lock is a single admin-only route that takes the desired boolean state and is idempotent. A locked post stays publicly readable, its existing comments stay readable, editable by their author, and deletable by author or admin, while a new root comment or reply answers 409.
- The legacy family, including the unguarded `toggleLock` path that let any signed-in user lock a post and a create that had been failing on a NOT NULL uuid since the uuid migration, was retired in RET-POST-01. The `routes/api.php` ledger records each replacement.

## Views, Downloads, and Hashtags

Migrated article/catalogue services can load or mutate engagement through shared application actions and repositories. These concerns enrich a feature response but do not own the feature's core content lifecycle.

## Failure Behavior

- unauthenticated write/toggle;
- invalid entity type or identifiers;
- invalid comment content or parent ID;
- parent/target not found where enforced;
- forbidden mutation when the viewer is neither the owner nor, where allowed, an admin;
- conflict when commenting or replying on a locked post;
- resource-specific read target not found.

## Evidence

- `processor-api/tests/Feature/Comments/`
- `processor-api/tests/Feature/Community/Posts/`
- `processor-api/tests/Feature/Routes/LegacyPostRouteRetirementTest.php`
- `processor-api/app/Http/v1/Comments/`
- `processor-api/app/Http/v1/Community/Posts/`
- `processor-api/app/Http/v1/Engagement/`
- `client/src/routes/community/`
