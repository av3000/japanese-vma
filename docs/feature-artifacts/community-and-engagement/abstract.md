# Community and Engagement — Abstract

> **Status:** Baseline; partial v1 engagement with legacy-heavy community posts
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
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

The v1 backend includes public article/catalogue comment reads, generic authenticated comment creation, and generic instance-like toggling. Shared engagement actions load stats, comments, hashtags, views, and downloads for migrated resources.

Community posts, comment update/delete, post moderation, sentence comments, and several like flows remain legacy. The v1 comment controller contains unimplemented update/delete methods, and reply inclusion is not yet a completed read capability.

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
