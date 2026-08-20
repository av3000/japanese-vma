# Community and Engagement — Behavior

> **Status:** Baseline; completed v1 behavior separated from stubs and legacy flows
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
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

Legacy resource-specific like routes still exist for articles, lists, posts, and comments. Migration must keep viewer state and counts coherent while callers move.

## Posts

Post list/detail/create/edit/delete/like/lock/comment behavior remains primarily legacy. Frontend post routes use raw legacy API calls. Backend migration is intentionally split into read, write/moderation, and comment slices.

## Views, Downloads, and Hashtags

Migrated article/catalogue services can load or mutate engagement through shared application actions and repositories. These concerns enrich a feature response but do not own the feature's core content lifecycle.

## Failure Behavior

- unauthenticated write/toggle;
- invalid entity type or identifiers;
- invalid comment content or parent ID;
- parent/target not found where enforced;
- forbidden mutation on legacy update/delete flows;
- inconsistent counts during partial caller migration;
- resource-specific read target not found.

## Evidence

- `processor-api/tests/Feature/Comments/`
- `processor-api/app/Http/v1/Comments/`
- `processor-api/app/Http/v1/Engagement/`
- `client/src/routes/community/`
