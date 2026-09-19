# Japanese Study Material — Current to Target

> **Status:** Migration map; each resource keeps its own contract
> **Last reviewed:** 2026-09-18
> **Evidence baseline:** Repository working tree inspected on 2026-09-18
> **Audience:** Implementers, data maintainers, planners, and reviewers

## Resource Position

| Resource | Current | Target | Completion signal |
|---|---|---|---|
| Kanji | v1 list/detail is the only registered read; the legacy read/search routes are retired. | Reached. | Met - no legacy kanji route exists; filters and detail are covered by route tests. |
| Radicals | v1 list/detail is the only registered read; the legacy read/search routes are retired. | Reached. | Met - no legacy radical route exists. |
| Words | v1 list/detail is the only registered read; the legacy read/search/relation routes are retired. | Reached. | Met - no legacy word route exists; list/detail/cache tests pass. |
| Sentences | v1 list/detail is the only registered read and v1 create/update/delete the only registered writes; the legacy read/search/relation routes (RET-JPN-READ-01) and write/comment routes (RET-SEN-01) are retired. | Reached. | Met - no legacy sentence route exists; owner/admin/imported authorization and relationship transactions are covered by SentenceAuthoringV1Test, comments and likes by the generic v1 contracts. |

## Legacy Route Ledger

The legacy public Japanese read, search and relation routes were removed in
RET-JPN-READ-01, and the Sentence write and comment routes in RET-SEN-01, once
each had an exact v1 replacement and zero routed React callers.

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
| `POST api/sentence` | `POST api/v1/sentences` |
| `PUT api/sentence/{id}` | `PUT api/v1/sentences/{uuid}` |
| `DELETE api/sentence/{id}` | `DELETE api/v1/sentences/{uuid}` |
| `POST api/sentence/{id}/comment` | `POST api/v1/comments` with `entity_type=sentence` |
| `PUT api/sentence/{id}/comment/{commentid}` | `PUT api/v1/comments/{uuid}` |
| `DELETE api/sentence/{id}/comment/{commentid}` | `DELETE api/v1/comments/{uuid}` |
| `POST api/sentence/{id}/comment/{commentid}/like` | `POST api/v1/like-instance` (one idempotent toggle) |
| `POST api/sentence/{id}/comment/{commentid}/unlike` | `POST api/v1/like-instance` (one idempotent toggle) |

No `JapaneseDataController` route is registered any more and the class is gone.
Two legacy write behaviours were dropped rather than carried over: sentence
create/update never completed (the word helper read an undefined `$article`
variable and raised on every call), and sentence update/delete had no owner,
admin or imported check. v1 enforces owner-or-admin writes and keeps imported
sentences immutable.
`tests/Feature/Routes/LegacyJapaneseReadRouteRetirementTest.php` guards the
reads and `tests/Feature/Routes/LegacySentenceRouteRetirementTest.php` the
writes; both assert the controller stays gone.

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
