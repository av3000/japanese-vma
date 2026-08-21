# Articles — Vocabulary

> **Status:** Baseline terminology
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product, frontend, backend, and documentation contributors

| Term | Meaning |
|---|---|
| **Article** | User-authored Japanese reading content with titles, body content, source, visibility, author, and related data. |
| **Author** | User identity presented as the article creator. |
| **Publicity** | Visibility state: Private or Public. |
| **Article status** | Moderation/processing-facing enum: Pending, Processed, Under Review, Rejected, or Approved. |
| **Processing status** | Asynchronous operation state: pending, processing, completed, or failed. |
| **Attached kanji** | Kanji associated with an article by processing or persistence relationships. |
| **Attached word** | Word associated with an article by processing or persistence relationships. |
| **Include option** | Detail-query flag controlling optional enrichment such as words or kanji. |
| **Engagement** | Comments, likes, views, downloads, and hashtags related to the article. |
| **Owner** | User allowed by backend policy to change or delete the article. |
| **Generated client** | Orval-produced frontend transport derived from the v1 OpenAPI contract. |

## Avoided Ambiguities

- Do not use **published** as a synonym for both public visibility and Approved moderation status; these are distinct concepts.
- Do not use **processed** to mean that every asynchronous operation succeeded unless the processing-status evidence confirms completion.
- Use **UUID** for the public v1 identity where the route expects it, and **numeric ID** only where the current contract explicitly does so.
- Use **hashtags** for the normalized domain concept even where a compatibility payload still accepts `tags`.

## Sources

- `processor-api/app/Domain/Shared/Enums/ArticleStatus.php`
- `processor-api/app/Domain/Shared/Enums/PublicityStatus.php`
- `processor-api/app/Domain/Shared/Enums/LastOperationStatus.php`
- `processor-api/app/Domain/Articles/`
