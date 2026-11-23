# Community and Engagement — Abstract

> **Status:** v1 community posts and engagement; every legacy Post, Comment and Like route retired
> **Last reviewed:** 2026-09-19
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; Post retirement (RET-POST-01) reflected 2026-09-19
> **Audience:** Product-minded engineers, reviewers, and AI-assisted contributors

## Goal

Community and engagement let users discuss content and express interaction across articles, catalogues, sentences, posts, and other supported entity types.

## Capability Groups

- community posts and post moderation;
- comments and replies;
- likes;
- hashtags;
- views and downloads;
- engagement summaries attached to resource responses.

## Current Shape

The v1 backend owns public article/catalogue/post/sentence comment reads with reply previews, generic authenticated comment create/update/delete, generic instance-like toggling, and the full community Post lifecycle: list with keyword/hashtag/topic/sort filters, UUID detail with transitional numeric resolution and view recording, owner create/update, owner-or-admin delete with transactional cleanup, and admin lock with an explicit state. Shared engagement actions load stats, comments, hashtags, views, and downloads for every migrated resource.

No legacy community or engagement route remains. The last family, the `PostController` routes in `routes/api.php`, was retired in RET-POST-01 (#152); the ledger comment in that file records each route's v1 replacement, and `tests/Feature/Routes/LegacyPostRouteRetirementTest.php` guards the boundary.

## Important Boundaries

- comments: `processor-api/app/Http/v1/Comments/`, `processor-api/app/Application/Comments/`, `processor-api/app/Domain/Comments/`
- shared engagement: `processor-api/app/Application/Engagement/` and `processor-api/app/Domain/Engagement/`
- entity types: `processor-api/app/Domain/Shared/Enums/ObjectTemplateType.php`
- community frontend: `client/src/routes/community/`
- migrated comment precedents: article and catalogue detail content components

## Out of Scope

- Designing a new social graph or notification system.
- Treating view/like counts as analytics-grade metrics.
- Defining new moderation statuses without product decisions.
- Bundling post migration, comment mutation migration, and all entity engagement into one implementation slice.

## Related Documents

- [Vocabulary](./vocabulary.md)
- [Behavior](./behavior.md)
- [Mutations](./mutations.md)
- [User stories](./user-stories.md)
- [Current-to-target](./current-to-target.md)
