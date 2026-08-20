# Community and Engagement — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product, frontend, backend, and documentation contributors

| Term | Meaning |
|---|---|
| **Post** | Community-authored discussion content, currently served primarily by legacy routes. |
| **Comment** | User-authored response attached to a supported entity. |
| **Reply** | Comment whose parent comment ID references another comment. |
| **Like** | Viewer interaction toggled for an entity through shared engagement storage. |
| **Hashtag** | Normalized tag associated with an article, catalogue, or other supported entity. |
| **View** | Recorded observation of a supported entity. |
| **Download** | Recorded generation/download interaction, commonly associated with PDF output. |
| **Engagement summary** | Counts and viewer-specific state assembled for a resource response. |
| **Entity type** | UUID-valued `ObjectTemplateType` identifying Article, Radical, Kanji, Word, Sentence, List, Post, Comment, or other supported categories. |
| **Legacy template ID** | Numeric mapping retained for existing polymorphic persistence tables. |
| **Entity tuple** | Entity type, numeric entity ID, and entity UUID supplied by the generic comment write contract. |

## Contract Language

- Comment **reads** are currently resource-specific for article and catalogue UUID routes.
- Comment **create** is entity-generic and validates the entity tuple plus content and optional parent comment ID.
- Like **toggle** uses a shared object-type boundary and numeric real-object ID.
- **List** is the current object-template label for the catalogue persistence category; use **catalogue** in product prose.

## Avoided Ambiguities

- Do not call reply inclusion complete while the controller records it as unimplemented.
- Do not describe v1 comment update/delete as available because controller method stubs exist.
- Do not treat a frontend like toggle as authorization evidence; backend middleware and application logic own it.

## Sources

- `processor-api/app/Domain/Shared/Enums/ObjectTemplateType.php`
- `processor-api/app/Http/v1/Comments/Requests/StoreCommentRequest.php`
- `processor-api/app/Application/Engagement/`
