# Articles — User Stories

> **Status:** Baseline stories grouped by verified and target capability
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, QA, and planners

## Verified Current Stories

### Discovery and reading

- As a visitor, I can browse articles so I can find Japanese reading material.
- As a visitor, I can open a public article and see its author, content, tags, and available engagement information.
- As a learner, I can inspect related words and kanji so the article becomes study material rather than plain text.
- As a learner, I can see processing state so I know when related study data is still being prepared.

### Authoring

- As an authenticated contributor, I can create an article with Japanese content, translation context, source, visibility, and tags.
- As an authorized owner, I can update my article without resending every field.
- As an authorized owner, I can delete an article I no longer want to maintain.
- As an owner, I can control whether the article is public or private.

### Study and engagement

- As a learner, I can add or remove an article from a compatible catalogue.
- As an authenticated user, I can comment on and like a supported article.
- As an authorized user, I can export article kanji or words as a PDF study aid.

## Target Stories

- As an administrator, I can list pending articles through a tested v1 contract.
- As an administrator, I can change article moderation status through a tested, authorized v1 contract.
- As a contributor, I receive clear failure and retry guidance when background processing fails.
- As a frontend maintainer, every article action uses generated v1 clients or a justified feature boundary rather than legacy endpoint strings.

## Requirement Links

- FR-ART-001 through FR-ART-005 in [Product Requirements](../../ai/product-requirements.md)
- FR-PROC-001 and FR-PROC-002 in [Product Requirements](../../ai/product-requirements.md)
- FR-PDF-001 in [Product Requirements](../../ai/product-requirements.md)
