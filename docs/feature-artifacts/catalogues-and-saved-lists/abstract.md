# Catalogues and Saved Lists — Abstract

> **Status:** Baseline; catalogue-v1 capability with explicit saved-list compatibility debt
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, reviewers, and AI-assisted contributors

## Goal

Catalogues let users organize articles and Japanese study items into owned, typed collections. They also support system-oriented known-item lists used to represent learned state.

## Users

- Visitors browse public catalogues.
- Authenticated learners create and maintain custom catalogues.
- Owners add and remove compatible items.
- Learners inspect whether an item is already saved or known.

## In Scope

- public and owner-filtered list/detail reads;
- create, partial update, and delete;
- typed item membership;
- catalogue-for-item picker and learned/saved state;
- publicity, hashtags, stats, comments, likes, views, and downloads;
- supported kanji/word PDF exports;
- legacy `/list` and `/lists` navigation compatibility.

## Important Boundaries

- v1 routes/resources: `processor-api/app/Http/v1/Catalogues/`
- orchestration/policies: `processor-api/app/Application/Catalogues/`
- domain types: `processor-api/app/Domain/Catalogues/`
- modern frontend boundary: `client/src/api/catalogues/`
- active routes: `client/src/routes/CataloguesList/`, `client/src/routes/CatalogueDetails/`, and catalogue form routes
- legacy redirects: `client/src/routes/CatalogueLegacyRedirects/`

## Current Shape

Catalogue v1 list, detail, create, update, delete, picker, item mutation, and kanji/word PDF behavior have focused backend coverage. Modern frontend list/detail routes use generated clients and React Query boundaries.

Legacy numeric list identity, saved-list names/routes, and radical/sentence PDF parity remain transitional. These seams are compatibility mechanisms, not templates for new work.

## Related Documents

- [Vocabulary](./vocabulary.md)
- [Behavior](./behavior.md)
- [Mutations](./mutations.md)
- [User stories](./user-stories.md)
- [Current-to-target](./current-to-target.md)
