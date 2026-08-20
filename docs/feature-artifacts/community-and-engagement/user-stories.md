# Community and Engagement — User Stories

> **Status:** Baseline stories grouped by verified and target capability
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, QA, and planners

## Verified Current Stories

### Comments and likes

- As a visitor, I can read comments on supported public articles and catalogues.
- As an authenticated user, I can add a comment to a supported entity.
- As an authenticated user, I can provide a parent comment ID when creating a reply.
- As an authenticated user, I can toggle my like on a supported entity.
- As a viewer, I can see engagement summaries exposed by migrated resource responses.

### Community posts

- As a visitor, I can browse community posts and open post details through the current legacy flow.
- As an authenticated contributor, I can create or edit a post where the legacy contract authorizes it.
- As an administrator, I can perform the existing post lock/moderation action.

### Cross-feature engagement

- As a learner, my views, likes, comments, tags, and downloads can be associated with articles or catalogues.
- As a content owner deleting a catalogue, related engagement records are removed by the owning application transaction.

## Target Stories

- As a user, I can update or delete my comment through a typed, authorized v1 contract.
- As a reader, reply inclusion behaves consistently and is covered by focused tests.
- As a community user, post list/detail reads use v1 contracts and React Query.
- As a post author or moderator, write and moderation actions use explicit v1 authorization.
- As a frontend maintainer, all generic likes and comments share generated contract types and stable invalidation rules.

## Requirement Links

- FR-ENG-001 through FR-ENG-006 in [Product Requirements](../../ai/product-requirements.md)
- NFR-SEC-001 and NFR-CON-001 in [Product Requirements](../../ai/product-requirements.md)
