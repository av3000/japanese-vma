# Japanese Study Material — Mutation Points

> **Status:** Baseline mutation inventory; v1 reference endpoints are primarily reads
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Backend, frontend, data, QA, and migration contributors

## User-Initiated Mutations

| Mutation | Owning boundary | Primary effect | State |
|---|---|---|---|
| Add resource to catalogue | Catalogue v1 item mutation | Persists membership in an owned compatible catalogue. | Verified current |
| Remove resource from catalogue | Catalogue v1 item mutation | Deletes existing membership. | Verified current |
| Mark resource learned | Known-catalogue behavior | Adds item to the matching learned-state catalogue. | Verified current for supported surfaces |
| Unmark resource learned | Known-catalogue behavior | Removes learned-state membership. | Verified current for supported surfaces |
| Create/update/delete sentence | Legacy sentence routes | Mutates user-contributed sentence data. | Legacy/debt pending v1 contract |
| Comment on sentence | Legacy sentence comment route | Persists sentence-related discussion. | Legacy/debt pending generic v1 migration |

## Processing and Import Mutations

| Mutation | Trigger | Effect |
|---|---|---|
| Import Japanese reference data | Explicit data import/migration workflow | Creates or updates reference tables and relationships. |
| Extract article kanji | Article processing job | Identifies kanji from Japanese article text. |
| Attach article kanji | Processing service/repository | Replaces or updates article-kanji relationships. |
| Extract article words | Article processing job | Tokenizes/finds words from Japanese article text. |
| Attach article words | Processing service/repository | Replaces or updates article-word relationships. |

## Read-Side Effects

Public reference reads should not mutate core Japanese reference data. Viewer-specific enrichment and view tracking, where present, belong to catalogue/engagement boundaries and must remain explicit.

## Constraints

- Public v1 Japanese endpoints are not evidence of v1 create/update/delete contracts.
- Imports must be deterministic and compatible with the expected dictionary schema.
- Article extraction and attachment are asynchronous and should be retry-safe.
- Catalogue mutations require authenticated ownership and type-compatible item IDs.
- Generated types must be repaired at the backend schema source when they degrade.
