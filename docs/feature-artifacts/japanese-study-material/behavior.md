# Japanese Study Material — Behavior

> **Status:** Baseline; resource-specific current and target behavior distinguished
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Frontend, backend, data, QA, and product-minded contributors

## Shared Read Behavior

- Kanji, radical, word, and sentence v1 list/detail endpoints are public.
- List requests normalize numeric pagination/filter values and reject invalid bounds.
- Results use resource-specific items plus pagination metadata.
- Missing detail identifiers return typed not-found behavior in migrated modules.
- Optional authenticated viewer state may enrich public results without making the base read private.

## Kanji Discovery

The kanji index supports pagination and filters including keyword, exact character, grade, JLPT, stroke range, meanings, on'yomi, kun'yomi, radical, and article UUID where supported.

Grade and JLPT values are validated by domain value-object rules. A maximum stroke count cannot be lower than the minimum.

## Radical Discovery

The radical index supports pagination plus keyword, radical text, meaning, hiragana, and stroke filters.

## Word Discovery

The word index supports pagination plus keyword, word, furigana, and JLPT-oriented filters. An include option can request viewer catalogue state, allowing lists to render learned/saved state without a per-row request.

## Sentence Discovery

The sentence index supports pagination plus keyword, content, Tatoeba entry, and user filters.

## Relationships

- Articles can attach extracted kanji and words asynchronously.
- Detail/list resources may expose relationships to other Japanese material when the contract supports them.
- Learners add supported resources to typed catalogues or known catalogues.
- A catalogue type determines whether an item is compatible.

## Frontend Behavior

The route layer should map URL/search state to generated v1 query parameters once, then let React Query own server state. Filters remain resource-specific; a shared packet does not justify a generic all-resource query abstraction.

Current route maturity differs. Kanji list/detail has typed v1 work and tests in the current checkout. Other resources include a mixture of migrated list behavior, legacy detail calls, and transitional catalogue/comment behavior.

## Failure Behavior

- invalid pagination or filter values return validation errors;
- missing resource identifiers return not found;
- generated schema drift can block safe frontend migration even when runtime data is correct;
- catalogue add/remove can reject incompatible types, duplicate membership, or unauthorized mutation;
- asynchronous article attachment can fail independently of the source article write.

## Evidence

- `processor-api/tests/Feature/JapaneseMaterial/`
- `processor-api/app/Http/v1/JapaneseMaterial/`
- `client/src/routes/japanese/`
