# Translation Management: Requirements

| | |
|---|---|
| Epic | [#659 TRANSLATIONS](https://github.com/av3000/japanese-vma/issues/659) |
| Milestone | Translation Management |
| Status | In progress: US-01 delivered |
| Design | [2026-08-18 design specification](./2026-08-18-translation-management-design.md) (how). This document covers the what. |
| Plan | [implementation-plan.md](./implementation-plan.md): branch model, per-story loop, story sections, run prompt |
| Last updated | 2026-11-06 |

## 1. Goal

Let administrators offer the site's interface in more languages with no deploy, and never show a learner a blank label. English stays the single code-owned source. Admins add a language in Filament, see what is missing and fill it in, and each save goes live immediately. The React client picks up the selected language through one shared boundary and falls back to English for anything untranslated.

## 2. Personas

| Persona | Who | Needs |
|---|---|---|
| Learner | Anonymous or signed-in visitor of the React site | Reads the interface in their own language, and never sees blanks or raw keys |
| Translator | Admin user with `translations.view` / `translations.update` | Finds missing labels, fills them in, and sees them live |
| Site administrator | Admin user with `locales.manage` and role management | Decides which languages exist and who may edit them |
| Developer | Contributor to this repo | Adds or renames English keys through code review, with one source of truth |

## 3. User stories

| ID | Story | Issue |
|---|---|---|
| US-01 | As a developer, I want one code-owned English catalogue that the API serves with its list of languages, so that every UI label has exactly one source and clients can discover the available languages. | [#660](https://github.com/av3000/japanese-vma/issues/660) |
| US-02 | As a learner, I want a language bundle that always contains every label, translated or in English, so that I never see a blank or missing label. | [#661](https://github.com/av3000/japanese-vma/issues/661) |
| US-03 | As a site administrator, I want to add, activate and deactivate languages, and grant translation rights separately, so that I decide what learners can choose and who may change it. | [#662](https://github.com/av3000/japanese-vma/issues/662) |
| US-04 | As a translator, I want an overview of every label for a language with a Missing filter and an exact completion figure, so that I know what is left to translate. | [#663](https://github.com/av3000/japanese-vma/issues/663) |
| US-05 | As a translator, I want to edit one label next to its English source and have the change live on save, so that I can translate without waiting for a deploy. | [#664](https://github.com/av3000/japanese-vma/issues/664) |
| US-06 | As a learner, I want to pick my language in the header and have it remembered, so that the site speaks my language on every visit. | [#665](https://github.com/av3000/japanese-vma/issues/665) |
| US-07 | As a learner, I want sign-in, my account controls and common buttons in my language, so that the parts I use most are understandable from day one. | [#666](https://github.com/av3000/japanese-vma/issues/666) |

Each issue holds that story's acceptance criteria in Given/When/Then form. This document does not repeat them.

## 4. Glossary

| Term | Meaning |
|---|---|
| Source catalogue | The code-owned English labels in `processor-api/resources/lang/en/ui.php`, flattened to dotted keys |
| Key | A stable semantic identifier such as `auth.sign_in` or `common.save` |
| Locale | A language the site can be shown in, with a code (`da`), a name ("Dansk") and an active flag |
| Override | A stored non-English value for one key in one locale |
| Translated / Missing | The only two states a key has for a locale: Translated when a non-empty override exists, Missing otherwise |
| Bundle | The complete flat key → text map for one locale, with overrides merged over English |
| Orphan | An override whose key no longer exists in the source catalogue |

## 5. Functional requirements

| ID | Requirement |
|---|---|
| FR-01 | English (`en`) is the source and fallback. Its labels are code-owned in the source catalogue, use stable dotted semantic keys, and are never empty. They change only through code review. A rename counts as removing one key and adding another, and existing translations are not moved. |
| FR-02 | A locale has a unique lowercase ISO 639-1 code, a name and an active flag. English is seeded and cannot be deactivated, re-coded or deleted. Non-English locales can be created, activated and deactivated. Deactivating hides a locale at runtime but keeps its overrides. There is no hard delete in the first delivery. |
| FR-03 | `GET /api/v1/translations/locales` is public and returns the active locales, marking `en` as the fallback. It is never shadowed by the parameterised bundle route. |
| FR-04 | `GET /api/v1/translations/{locale}` is public. It returns `{ locale, fallback_locale, messages }`, a complete flat map with non-empty overrides merged over English. `en` returns the source catalogue. An unknown or inactive locale returns a typed 404 consistent with other v1 endpoints. |
| FR-05 | Only non-English overrides are stored, unique per (locale, key). Saving trims the value. A blank result deletes the override, and non-empty text upserts it. Values are limited to 2,000 characters. Saves for English, for unknown keys or for inactive locales are rejected and write nothing. No public write endpoint exists. |
| FR-06 | Only the two derived states exist: Translated and Missing. There is no draft, publish, stale, approval or revision state, and no status column. |
| FR-07 | Orphans never appear in bundles or completion counts. They are left in place, and cleanup tooling is deferred. |
| FR-08 | Completion for a locale = valid translated keys ÷ English keys. It is shown as `N of M translated · P% complete`. |
| FR-09 | The Filament translation overview lists every English key for the selected active non-English locale, including keys with no stored row. It has search across key, English and translated text, All and Missing filters, Translated and Missing badges only, a completion line, and rows that link to the editor. |
| FR-10 | The Filament translation editor shows the key and English text read-only, the selected locale, one textarea with the helper "Leave empty to use English", an English fallback preview when the field is empty, one "Save translation" action, and confirmation that the change is live. Saving against a removed key or a deactivated locale shows an error and writes nothing. |
| FR-11 | The permission catalogue gains `translations.view`, `translations.update` and `locales.manage`. Access to the admin dashboard alone grants none of them. |
| FR-12 | The React client exposes translations through one `TranslationProvider` and a `t(key)` hook. Components never fetch bundles directly. `t()` returns the key itself when the key is missing from the bundle. |
| FR-13 | The client chooses its locale in this order: the persisted choice if still active, then a supported browser base language, then `en`. The choice is persisted locally, and `<html lang>` follows the active locale. |
| FR-14 | Changing language fetches one complete bundle and updates visible labels without a page reload. |
| FR-15 | The header language selector is shown only when more than one locale is active. |
| FR-16 | A representative set of surfaces reads its labels through `t()`: header account controls, the Login form, shared form buttons and one empty state. Strings not yet migrated stay hard-coded English. |

## 6. Non-functional requirements

| ID | Category | Requirement |
|---|---|---|
| NFR-01 | Performance | Building a bundle, the overview list or a completion count runs a fixed number of queries whatever the number of keys (no N+1). |
| NFR-02 | Freshness | A saved override appears in the next bundle response. There is no caching layer (Redis or long `Cache-Control`) in the first delivery. |
| NFR-03 | Security | Writes happen only through authenticated, permission-checked Filament actions. Translated values are treated as text everywhere, never rendered as HTML. |
| NFR-04 | Contract | Endpoints are documented through Scramble. The client types come from `composer openapi` then `npm run orval:file`, and generated files are never hand-edited. |
| NFR-05 | Accessibility | The language selector is keyboard-operable and labelled, and names the current language in text, not by colour or flag alone. `<html lang>` matches the shown language. |
| NFR-06 | Resilience | The client never shows blank labels. The previous bundle stays rendered while a new one loads and when a request fails, and the failure shows as a non-blocking error. |
| NFR-07 | Compatibility | With only English active, the live UI is unchanged and shows no selector. All schema changes are additive, so the change is a two-way door. |
| NFR-08 | Verification | DB-backed tests run in the Docker test lane. UI states have Vitest tests and Storybook stories. The PR carries desktop, tablet and mobile screenshots. |
| NFR-09 | Concurrency | Concurrent edits to the same key resolve as last write wins. Timestamps are kept for diagnosis, and optimistic locking is out of scope. |

## 7. Out of scope

- Draft, review, publish, stale-tracking, history or rollback workflows.
- Machine translation, external translation-management services, and CSV or spreadsheet import and export.
- Translating domain content: articles, comments, catalogues, dictionary data, and Builder-managed menus and blog posts (BUILDER-CMS, #584).
- ICU messages, plurals, interpolation, RTL layout, and Spatie Translatable or JSON model fields.
- Migrating every remaining hard-coded string. That is done feature by feature in follow-up issues.
- Production rollout of real locales.

## 8. Assumptions and constraints

- The first target locales are simple ISO 639-1 codes. Region codes are added when needed.
- Translators are existing admin-panel users who are given the new permissions.
- The catalogue stays at a few hundred keys, so one flat bundle per locale is enough.
- No third-party translation package is installed. Adopting i18next needs its own decision, made when plurals or interpolation come into scope.

## 9. Traceability

| Requirement | Stories | Status |
|---|---|---|
| FR-01 | US-01 | Delivered |
| FR-02 | US-01 (English row), US-03 | English row delivered (US-01); the rest planned |
| FR-03 | US-01 | Delivered |
| FR-04 | US-01, US-02, US-07 (end to end) | English path delivered (US-01); the rest planned |
| FR-05 | US-02, US-05, US-07 (end to end) | Planned |
| FR-06 | US-02, US-04 | Planned |
| FR-07 | US-02, US-04 | Planned |
| FR-08 | US-02, US-04 | Planned |
| FR-09 | US-04 | Planned |
| FR-10 | US-05 | Planned |
| FR-11 | US-03, US-04, US-05 | Planned |
| FR-12 | US-06 | Planned |
| FR-13 | US-06 | Planned |
| FR-14 | US-06, US-07 (end to end) | Planned |
| FR-15 | US-06 | Planned |
| FR-16 | US-07 | Planned |
| NFR-01 | US-02, US-04 | Planned |
| NFR-02 | US-02, US-05 | Planned |
| NFR-03 | US-03, US-05 | Planned |
| NFR-04 | US-01 | Delivered |
| NFR-05 | US-06 | Planned |
| NFR-06 | US-06 | Planned |
| NFR-07 | US-06, US-07 | Planned |
| NFR-08 | all | Planned |
| NFR-09 | US-02, US-05 | Planned |

Each story's commit updates its rows to **Delivered**. When the epic merges, this document is reviewed again. Once the code and tests show the rules on their own, it is removed or reduced.
