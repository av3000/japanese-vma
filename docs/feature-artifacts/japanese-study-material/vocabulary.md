# Japanese Study Material — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product, frontend, backend, data, and documentation contributors

| Term | Meaning |
|---|---|
| **Kanji** | Logographic character with readings, meanings, grade/JLPT metadata, stroke information, and radical relationships. |
| **Radical** | Character component used for organization, lookup, and meaning/reading context. |
| **Word** | Lexical entry with written form, reading/furigana, meanings, and related metadata. |
| **Sentence** | Japanese example text with translation or source metadata where available. |
| **On'yomi** | Reading historically derived from Chinese pronunciation. |
| **Kun'yomi** | Native Japanese reading. |
| **Furigana** | Reading aid displayed alongside written Japanese. |
| **JLPT level** | Japanese-Language Proficiency Test classification used for filtering. |
| **Grade** | School-grade classification used by kanji discovery. |
| **Stroke count** | Number of strokes used to write a kanji or radical. |
| **Tatoeba entry** | External/community sentence reference stored with supported sentence data. |
| **Viewer catalogue state** | Server-derived learned/saved membership for the authenticated viewer. |
| **Extraction** | Process of finding Japanese units in article text. |
| **Attachment** | Persistence of an extracted unit's relationship to an article. |

## Identity Guidance

- Kanji and radicals may be addressed by character-like identifiers.
- Words and sentences use their established v1 identifier contracts.
- Catalogue item mutations currently use numeric item IDs even when detail routes expose another public identifier.
- Route parameters must be parsed once and matched to the generated contract; do not pass ambiguous strings through several layers.

## Avoided Ambiguities

- Do not use **kanjis** as a linguistic plural in prose; use **kanji**. Preserve `kanjis` only in route/file identifiers.
- Do not describe **known** and **saved** as the same state. Known means learned-state catalogue membership; saved means membership in a study catalogue.
- Do not call extraction synchronous merely because the article write starts it.

## Sources

- `processor-api/app/Domain/JapaneseMaterial/`
- `processor-api/app/Domain/Shared/ValueObjects/JlptLevels.php`
- `processor-api/app/Application/Catalogues/Services/ViewerCatalogueStateService.php`
