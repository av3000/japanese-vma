# Japanese Study Material — Current to Target

> **Status:** Migration map; each resource keeps its own contract
> **Last reviewed:** 2026-09-09
> **Evidence baseline:** Repository working tree inspected on 2026-09-09
> **Audience:** Implementers, data maintainers, planners, and reviewers

## Resource Position

| Resource | Current | Target | Completion signal |
|---|---|---|---|
| Kanji | v1 list/detail is the only registered read; the legacy read/search routes are retired. | Reached. | Met - no legacy kanji route exists; filters and detail are covered by route tests. |
| Radicals | v1 list/detail is the only registered read; the legacy read/search routes are retired. | Reached. | Met - no legacy radical route exists. |
| Words | v1 list/detail is the only registered read; the legacy read/search/relation routes are retired. | Reached. | Met - no legacy word route exists; list/detail/cache tests pass. |
| Sentences | v1 list/detail is the only registered read; the legacy read/search/relation routes are retired. Write and comment flows are still legacy. | v1 write/comment contracts (RET-SEN-01). | Reads met; writes still need explicit authorization and focused tests. |

## Legacy Route Ledger

The legacy public Japanese read, search and relation routes were removed in
RET-JPN-READ-01 once each had an exact v1 replacement and zero routed React
callers.

| Retired legacy route | v1 replacement |
|---|---|
| `GET api/kanjis` | `GET api/v1/kanjis` |
| `GET api/kanji/{kanji}` | `GET api/v1/kanjis/{identifier}` |
| `POST api/kanjis/search` | `GET api/v1/kanjis` query filters |
| `GET api/radicals` | `GET api/v1/radicals` |
| `GET api/radical/{radical}` | `GET api/v1/radicals/{identifier}` |
| `POST api/radicals/search` | `GET api/v1/radicals` query filters |
| `GET api/words` | `GET api/v1/words` |
| `GET api/word/{id}` | `GET api/v1/words/{identifier}` |
| `GET api/word/{id}/kanjis` | `kanjis` on the v1 word detail payload |
| `POST api/words/search` | `GET api/v1/words` query filters |
| `GET api/sentences` | `GET api/v1/sentences` |
| `GET api/sentence/{id}` | `GET api/v1/sentences/{identifier}` |
| `GET api/sentence/{id}/kanjis` | `kanjis` on the v1 sentence detail payload |
| `GET api/sentence/{id}/words` | `words` on the v1 sentence detail payload |
| `POST api/sentences/search` | `GET api/v1/sentences` query filters |

Every `JapaneseDataController` route still registered is classified below; none
of them is a read.

| Retained legacy route | Why it stays | Retires under |
|---|---|---|
| `POST api/sentence` | Sentence create has no v1 owner yet. | RET-SEN-01 |
| `PUT api/sentence/{id}` | Sentence update has no v1 owner yet. | RET-SEN-01 |
| `DELETE api/sentence/{id}` | Sentence delete has no v1 owner yet. | RET-SEN-01 |
| `POST api/sentence/{id}/comment` | Sentence comment create has no v1 owner yet. | RET-SEN-01 |
| `PUT api/sentence/{id}/comment/{commentid}` | Sentence comment update has no v1 owner yet. | RET-SEN-01 |
| `DELETE api/sentence/{id}/comment/{commentid}` | Sentence comment delete has no v1 owner yet. | RET-SEN-01 |
| `POST api/sentence/{id}/comment/{commentid}/like` | Sentence comment like has no v1 owner yet. | RET-SEN-01 |
| `POST api/sentence/{id}/comment/{commentid}/unlike` | Sentence comment unlike has no v1 owner yet. | RET-SEN-01 |
| `POST api/user/list/contain` | Catalogue membership check; belongs to the Catalogue lane, not this one. | Catalogue retirement |

Those routes keep `mb_str_split`, `getKanjiIdsFromText`, `getWordIdsFromText`
and `checkIfBelongToList` alive in `JapaneseDataController`. The helpers that
served only the retired reads went with them.
`tests/Feature/Routes/LegacyJapaneseReadRouteRetirementTest.php` guards both
directions.

## Cross-Resource Target Flow

```text
route search/identifier mapping
  -> resource-specific query hook
  -> generated v1 client
  -> resource-specific backend request/service/repository/resource
```

Catalogue membership remains a shared adjacent boundary rather than being reimplemented in every resource module.

## Migration Constraints

- Fix OpenAPI response shape before frontend type coercion.
- Preserve exact resource filters and pagination behavior during migration.
- Decide detail aggregation per resource; do not assume every relationship belongs in the base response.
- Coerce numeric route IDs once and reject invalid values before writes.
- Migrate shared catalogue behavior consistently across kanji, radical, word, and sentence details.
- Keep article extraction work separate from public Japanese-resource route migration.

## Explicit Non-Goals

- One generic query model for all four resources.
- A new import pipeline as part of frontend route migration.
- A broad cache/index project without measured pressure.
- Hand-edited generated client types.

## Evidence

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `client/AGENTS.md`
- `processor-api/tests/Feature/JapaneseMaterial/`
- `client/src/routes/japanese/`
