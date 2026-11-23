# Japanese Study Material — User Stories

> **Status:** Baseline stories grouped by verified and target capability
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, data contributors, QA, and planners

## Verified Current Stories

### Discovery

- As a learner, I can browse kanji and filter by linguistic attributes so I can focus my study.
- As a learner, I can browse radicals by character, reading, meaning, or strokes.
- As a learner, I can browse words by written form, furigana, keyword, or JLPT-related data.
- As a learner, I can browse sentences by content, keyword, author, or Tatoeba reference where present.
- As a learner, I can open a detail page for each core resource family.

### Study organization

- As an authenticated learner, I can add a compatible item to one of my catalogues.
- As an authenticated learner, I can remove an item from a catalogue.
- As a learner, I can see whether supported items are learned or saved without issuing a separate request for every row where batched state is available.

### Article relationships

- As a reader, I can use article-related kanji and words as study material.
- As a contributor, article processing can derive kanji and word relationships from Japanese text.

## Target Stories

- As a learner, every active Japanese list and detail screen uses a typed v1 query boundary.
- As a learner, related-resource detail data is predictable and explicitly controlled rather than assembled by legacy calls.
- As a frontend maintainer, kanji, radical, word, and sentence routes share conventions without collapsing their different filters into a generic abstraction.
- As a backend maintainer, runtime resource shapes, OpenAPI schemas, and generated TypeScript types agree.
- As a sentence contributor, create/edit/delete and comment workflows use authorized v1 contracts.

## Requirement Links

- FR-JP-001 through FR-JP-007 in [Product Requirements](../../ai/product-requirements.md)
- FR-PROC-001 and FR-PROC-002 in [Product Requirements](../../ai/product-requirements.md)
