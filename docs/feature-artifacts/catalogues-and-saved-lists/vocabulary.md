# Catalogues and Saved Lists — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product, frontend, backend, and documentation contributors

| Term | Meaning |
|---|---|
| **Catalogue** | Preferred v1/product term for a typed, owned collection of study or content items. |
| **Saved list** | Legacy product/code term for catalogue behavior; use only when naming existing compatibility surfaces. |
| **Custom catalogue** | User-created catalogue with a type for radicals, kanji, words, sentences, or articles. |
| **Known catalogue** | System-oriented catalogue representing items marked as learned. |
| **Catalogue type** | Enum determining compatible item kind and display behavior. |
| **Catalogue item** | Numeric identity of an article or study resource associated with a catalogue. |
| **For-item picker** | Authenticated read returning owned catalogues relevant to an item and membership state. |
| **Contains item** | Server-derived indicator that the selected item belongs to a catalogue. |
| **Publicity** | Private/Public visibility state. |
| **Legacy list ID** | Numeric identity used by old `/list/:id` navigation and API routes. |
| **Catalogue UUID** | Preferred v1 identity for catalogue detail and mutation routes. |

## Type Families

Known catalogues use the first four legacy numeric types. User-created custom catalogues use types for radicals, kanji, words, sentences, and articles. The backend request contract currently accepts custom types 5 through 9.

## Avoided Ambiguities

- Use **catalogue** in new architecture and user-facing documentation; preserve **list** only when identifying legacy code or wire contracts.
- Do not call a catalogue **known** merely because it contains an item; known catalogues are a specific learned-state family.
- Do not treat numeric legacy IDs and UUIDs as interchangeable.
- Do not use frontend label arrays as contract definitions; backend/generated enums own wire values.

## Sources

- `processor-api/app/Domain/Shared/Enums/SavedListType.php`
- `processor-api/app/Domain/Shared/Enums/CatalogueType.php`
- `processor-api/app/Domain/Catalogues/`
