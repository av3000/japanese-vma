# Japanese Study Material — Abstract

> **Status:** Baseline; public v1 reads with resource-specific frontend migration state
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, data contributors, reviewers, and AI-assisted contributors

## Goal

Japanese study material turns reference data into browsable, searchable learning resources. The core resource families are kanji, radicals, words, and sentences, with relationships to articles and catalogues.

## Users

- Visitors browse and inspect public reference material.
- Learners filter by linguistic attributes and save or mark items as learned.
- Contributors encounter related study data while reading or authoring articles.
- Data maintainers import and validate upstream Japanese datasets.

## Resource Families

| Resource | Main learning role |
|---|---|
| Kanji | Character, readings, meanings, grade, JLPT, stroke count, radical relationships. |
| Radical | Character component, meaning, reading, and stroke count. |
| Word | Written form, furigana/readings, meanings, and JLPT-related discovery. |
| Sentence | Japanese example content, translation/context, author/source, and Tatoeba reference where present. |

## In Scope

- public list/detail v1 routes;
- resource-specific search and filters;
- pagination;
- relations among study resources and articles;
- catalogue membership and learned state;
- article extraction/attachment boundaries;
- frontend legacy-to-v1 migration state;
- generated schema accuracy.

## Important Boundaries

- backend HTTP: `processor-api/app/Http/v1/JapaneseMaterial/`
- application/domain modules: `processor-api/app/Application/JapaneseMaterial/` and `processor-api/app/Domain/JapaneseMaterial/`
- persistence/import code: `processor-api/app/Infrastructure/Persistence/` and `processor-api/database/`
- frontend routes: `client/src/routes/japanese/`

## Current Shape

All four resource families have public v1 list/detail routes and focused backend tests. Frontend migration depth varies: kanji has a stronger current typed route precedent, while several radical, word, sentence, and detail paths still contain legacy calls or transitional contracts.

The baseline does not combine these resources into one domain entity. They share a packet because learners navigate them together, while each keeps its own query criteria and response shape.

## Related Documents

- [Vocabulary](./vocabulary.md)
- [Behavior](./behavior.md)
- [Mutations](./mutations.md)
- [User stories](./user-stories.md)
- [Current-to-target](./current-to-target.md)
