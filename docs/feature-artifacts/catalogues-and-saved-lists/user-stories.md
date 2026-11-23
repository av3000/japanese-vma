# Catalogues and Saved Lists — User Stories

> **Status:** Baseline stories grouped by verified and target capability
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, QA, and planners

## Verified Current Stories

### Discovery

- As a visitor, I can browse public catalogues so I can discover curated study material.
- As a visitor, I can open a public catalogue and inspect its items and available engagement data.
- As an owner, I can view my private catalogue while unauthorized users cannot.

### Management

- As an authenticated learner, I can create a typed catalogue.
- As an owner, I can change catalogue title, type, visibility, or tags.
- As an owner, I can delete a catalogue and its related data.
- As an owner, I can add a compatible item and remove an existing item.
- As a learner, I can see which owned catalogues contain the item I am viewing.
- As a learner, I can mark supported Japanese items as learned through known-catalogue state.

### Output and compatibility

- As an authorized user, I can export supported kanji or word catalogues as PDF.
- As a user following an old saved-list URL, I am redirected toward the catalogue experience instead of reaching an unrouted screen.

## Target Stories

- As a user following a legacy numeric list URL, the identity resolves through a documented v1 compatibility contract.
- As a frontend maintainer, active catalogue routes contain no raw legacy endpoint strings.
- As a learner, supported radical and sentence catalogue exports use the same v1 service boundary as kanji and words.
- As a maintainer, obsolete SavedList route modules can be deleted after import and caller verification.

## Requirement Links

- FR-CAT-001 through FR-CAT-006 in [Product Requirements](../../ai/product-requirements.md)
- FR-PDF-002 and FR-PDF-003 in [Product Requirements](../../ai/product-requirements.md)
