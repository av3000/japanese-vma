# Community and Engagement — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-09-15
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; comment thread terms and contract language refreshed 2026-09-15
> **Audience:** Product, frontend, backend, and documentation contributors

| Term | Meaning |
|---|---|
| **Post** | Community-authored discussion content with a topic, hashtags, and an admin-controlled lock, served by the v1 `posts` routes. |
| **Comment** | User-authored response attached to a supported entity. |
| **Reply** | Comment whose parent comment ID references another comment. |
| **Root comment** | Comment with no parent comment. Only root comments are page entries in a thread read; use this rather than "top-level" or "parent" in prose. |
| **Thread** | A root comment together with every reply beneath it, at any depth. |
| **Reply preview** | Bounded, oldest-first slice of a thread's replies returned inline with its root comment. |
| **Replies count** | Size of a thread excluding its root. Independent of how many replies were loaded, so a root may report forty with none attached. |
| **Like** | Viewer interaction toggled for an entity through shared engagement storage. |
| **Hashtag** | Normalized tag associated with an article, catalogue, or other supported entity. |
| **View** | Recorded observation of a supported entity. |
| **Download** | Recorded generation/download interaction, commonly associated with PDF output. |
| **Engagement summary** | Counts and viewer-specific state assembled for a resource response. |
| **Entity type** | UUID-valued `ObjectTemplateType` identifying Article, Radical, Kanji, Word, Sentence, List, Post, Comment, or other supported categories. |
| **Legacy template ID** | Numeric mapping retained for existing polymorphic persistence tables. |
| **Entity tuple** | Entity type, numeric entity ID, and entity UUID supplied by the generic comment write contract. |

## Contract Language

- Comment **reads** are resource-specific UUID routes for article, catalogue, post, and sentence, plus a thread-agnostic replies route keyed by comment UUID.
- A thread read returns **root comments** only; pagination therefore counts conversations, not rows. Each root carries its **replies count** and, when asked, a **reply preview**; the replies route serves the rest of a thread on demand.
- Comment **create** is entity-generic and validates the entity tuple plus content and optional parent comment ID.
- Like **toggle** uses a shared object-type boundary and numeric real-object ID.
- **List** is the current object-template label for the catalogue persistence category; use **catalogue** in product prose.

## Avoided Ambiguities

- Do not read a **replies count** of zero as "no replies were loaded"; the count is always the true thread size, and an empty **reply preview** beside a non-zero count is a normal response rather than truncation.
- Do not describe a **reply preview** as the whole thread, or nest replies beneath replies in a response; a subtree is returned flat and oldest-first.
- Do not treat a frontend like toggle as authorization evidence; backend middleware and application logic own it.

## Sources

- `processor-api/app/Domain/Shared/Enums/ObjectTemplateType.php`
- `processor-api/app/Http/v1/Comments/Requests/StoreCommentRequest.php`
- `processor-api/app/Application/Engagement/`
