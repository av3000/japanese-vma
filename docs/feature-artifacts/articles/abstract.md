# Articles — Abstract

> **Status:** Baseline; verified v1 capability with identified legacy gaps
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, reviewers, and AI-assisted contributors

## Goal

Articles let contributors publish Japanese reading material with Japanese and optional English content, a source link, visibility, tags, related study data, engagement, and downloadable study output.

## Users

- Visitors discover and read public articles.
- Authenticated contributors create articles and maintain articles they are authorized to change.
- Learners use related kanji/word data and catalogue actions while reading.
- Administrators review article status where moderation contracts are implemented.

## In Scope

- list and detail discovery;
- create, update, and delete;
- publicity and moderation status;
- hashtags and engagement enrichment;
- asynchronous kanji/word processing;
- processing-status feedback;
- kanji and word PDF exports;
- catalogue membership actions shown on article details.

## Important Boundaries

- v1 routes and HTTP mapping: `processor-api/app/Http/v1/Articles/`
- orchestration and jobs: `processor-api/app/Application/Articles/`
- domain types: `processor-api/app/Domain/Articles/`
- frontend data boundary: `client/src/api/articles/`
- active route surfaces: `client/src/routes/ArticlesList/`, `client/src/routes/ArticleDetails/`, and article form routes

## Current Shape

The article list, detail, create, update, delete, related-word, processing, and supported PDF flows have substantial v1 implementation and focused tests. The frontend uses React Query and generated clients for major list/detail/write paths.

Administrative status/pending routes and a small number of helpers still carry explicit incomplete or legacy markers. They must not be described as complete merely because a route is registered.

## Out of Scope

- Defining new editorial workflow states.
- Replacing the current asynchronous extraction design.
- Redesigning the article UI.
- Retiring legacy article routes before caller verification.

## Related Documents

- [Vocabulary](./vocabulary.md)
- [Behavior](./behavior.md)
- [Mutations](./mutations.md)
- [User stories](./user-stories.md)
- [Current-to-target](./current-to-target.md)
