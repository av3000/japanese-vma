# Translation Management: Implementation Plan

| | |
|---|---|
| Epic | [#659 TRANSLATIONS](https://github.com/av3000/japanese-vma/issues/659) |
| Requirements | [requirements.md](./requirements.md): FR-nn and NFR-nn are cited below |
| Design | [2026-08-18 design specification](./2026-08-18-translation-management-design.md) |
| Integration branch | `feature/issue-659-translations` |
| Last updated | 2026-11-08 (Project K comparison decisions D-K1 to D-K7, §4.6) |

This is the plan for the whole epic. Each story session reads **its own section** (§5) together with **its issue**, writes a story-level plan from them, and then builds it. The issue's acceptance criteria are the contract. This plan only says how to meet them.

## 1. Story keys

Every commit message starts with the story key. The key maps to an issue like this:

| Key | Issue | Outcome | Blocked by |
|---|---|---|---|
| `TRANSLATIONS-01` | [#660](https://github.com/av3000/japanese-vma/issues/660) | English catalogue, locale list and `en` bundle API | — |
| `TRANSLATIONS-02` | [#661](https://github.com/av3000/japanese-vma/issues/661) | Target-locale overrides merged into bundles | 01 |
| `TRANSLATIONS-03` | [#662](https://github.com/av3000/japanese-vma/issues/662) | Translation permissions and Filament locale management | 02 |
| `TRANSLATIONS-04` | [#663](https://github.com/av3000/japanese-vma/issues/663) | Filament translation overview | 03 |
| `TRANSLATIONS-05` | [#664](https://github.com/av3000/japanese-vma/issues/664) | Filament translation editor | 04 |
| `TRANSLATIONS-06` | [#665](https://github.com/av3000/japanese-vma/issues/665) | React `TranslationProvider` and header language selector | 02 |
| `TRANSLATIONS-07` | [#666](https://github.com/av3000/japanese-vma/issues/666) | Representative UI on translation keys | 05, 06 |

The stories run **one at a time, in key order**. Story 06 could technically start after 02, but it waits its turn so that each merge into the integration branch is reviewed on its own.

## 2. Branch and commit model

```
develop ──► feature/issue-659-translations   (integration branch, refreshed from origin/develop before each story starts)
               ├─► story branch 01 ──PR──► squash-merge "TRANSLATIONS-01: … (#PR)"
               ├─► story branch 02 ──PR──► squash-merge "TRANSLATIONS-02: … (#PR)"
               └─ …                                         final PR ──► develop
```

- **Integration branch:** `feature/issue-659-translations`. Before the first story starts, merge `origin/develop` into it and push. Repeat that before each later story when develop has moved. Use a merge, not a rebase, so nobody force-pushes a shared branch.
- **Story branch:** cut from the tip of the integration branch. The session tool adds its own prefix and suffix to the name. The slug is `translations-0N`.
- **Commits on a story branch:** every one starts with the key and carries `Refs #<issue>` in the body:
  - `TRANSLATIONS-0N: <outcome>` for the implementation;
  - `TRANSLATIONS-0N: address the review on #<pr>` for the review fixes.

  There are no attribution trailers.
- **Story PR:** base is `feature/issue-659-translations`, never `develop`. Title `[TRANSLATIONS-0N] <outcome>`. The body follows `create-pull-request`, with the AC evidence table, `Refs #<issue>`, the risk line, verification, and screenshots for any visible UI (Filament pages included).
- **Merge:** `gh pr merge <pr> --squash --subject "TRANSLATIONS-0N: <outcome> (#<pr>)" --body "Refs #<issue>"`. The integration branch then holds exactly one commit per story. Keep the story branch, because the PR keeps the separate implementation and review commits for reading later.
- **Traceability:** each story's implementation commit marks the requirement rows it delivers as **Delivered** in `requirements.md` §9.
- **After the merge:** close the story issue by hand, because `Refs` never auto-closes it.

## 3. The loop for one story

| Step | Who | What |
|---|---|---|
| 1 | Orchestrator | `git fetch`, update the integration branch (§2), and check that the previous story is merged and its issue closed. |
| 2 | Orchestrator | Start the **implementation session**: Sonnet, effort high, its own worktree cut from the integration branch. |
| 3 | Implementer | Read the issue, this plan's §4 and its own §5, and the scoped `AGENTS.md`. Write the story plan in its first reply, as files, steps, tests and checks mapped to each AC. Build it test-first (`tdd`), one behaviour at a time. |
| 4 | Implementer | Run the checks in §4.5, commit `TRANSLATIONS-0N: …`, push, and open the PR into the integration branch. Report the PR number and branch name. |
| 5 | Orchestrator | Start the **review session**, read-only, in its own worktree cut from the story branch. |
| 6 | Reviewer | Run `review-branch` for the PR (Spec against the issue ACs, Standards against the repo rules) and post it with `post-review-to-pr`. Then run `/code-review high <pr> --comment`. Report the counts and the verdict. |
| 7 | Orchestrator | Send the implementer a message: run `/address-pr-review <pr>`, under the standing approval in the run prompt. |
| 8 | Implementer | Address every thread, in one fix commit `TRANSLATIONS-0N: address the review on #<pr>`, re-run the checks, push, then reply and resolve. |
| 9 | Orchestrator | Confirm that no Critical thread is left open and CI (if it runs) is green. Squash-merge, close the issue, and move to the next story. |

Neither session is archived or deleted. The user reads them afterwards.

## 4. Conventions every story follows

### 4.1 Backend layout (follow the neighbouring Study and Operations code)

| Piece | Where |
|---|---|
| Domain types (DTOs, value objects, errors, enums) | `app/Domain/Translations/…` |
| Service and repository interfaces | `app/Application/Translations/Services`, `app/Application/Translations/Interfaces/Repositories` |
| Eloquent models and repositories | Wherever the neighbouring modules keep them. Check `backend-standards` `ARCH-` and `DATA-` before choosing between `app/Domain/*/Models` and `app/Infrastructure/Persistence/Models`. |
| Bindings | `RepositoryServiceProvider` |
| HTTP | `app/Http/v1/Translations/{Controllers,Resources}`, with routes in `routes/api_v1.php` inside the `v1` prefix group, public (no auth middleware) |
| Results | `app/Shared/Results` (Result and TypedResults). Not-found errors follow the `…Errors::notFound()` pattern of the other domains. |
| Filament | `app/Filament/Pages/Translations/…` and `app/Filament/Resources/TranslationLocales/…`. Navigation via `AdminNavigationGroup`, access via `PanelActor::may(PanelPermission::…)`, and resource policies extend `PanelResourcePolicy`. |
| Permissions | A new `translations` group in `config/permission-catalog.php`, plus matching cases on the `PanelPermission` enum (`TranslationsView`, `TranslationsUpdate`, `LocalesManage`). |

#### Interfaces by layer (settled after the Project K comparison, §4.6)

```
Domain/Translations/
  DTOs/TranslationBundleDTO          {locale, fallbackLocale, messages}             01
  DTOs/TranslationCompletionDTO      {translated, total, percent}                   02
  DTOs/TranslationRowDTO             {key, source, value|null, state}               04
  Enums/TranslationState             Translated | Missing                           02
  Services/TranslationResolver       pure, with no IO. The only place the override rule lives.   02
      merge(array $source, array $overrides): array<string,string>   // override if non-empty and key in source, else source; orphans dropped
      state(?string $override): TranslationState
      completion(array $source, array $overrides): TranslationCompletionDTO
  Errors/TranslationErrors           localeNotFound, localeInactive, unknownKey, sourceLocaleNotEditable, valueTooLong

Application/Translations/
  Interfaces/Sources/EnglishSourceCatalogueInterface
      messages(): array<string,string>; find(string $key): ?string                  01
  Interfaces/Repositories/TranslationLocaleRepositoryInterface
      findActive(); findActiveByCode(string)                                        01
      findByCode(string); create(string $code, string $name); setActive(string $code, bool $active)   03
  Interfaces/Repositories/TranslationOverrideRepositoryInterface                    02
      overridesFor(int $localeId): array<string,string>       // one query
      upsert(int $localeId, string $key, string $value, ?int $userId): void
      delete(int $localeId, string $key): void
  Services/TranslationServiceInterface          read side: used by HTTP only        01
      activeLocales(): Result; bundle(string $code): Result<TranslationBundleDTO>
  Services/TranslationEditorServiceInterface    edit side: used by Filament only    02 / 04 / 05
      completion(string $code): Result<TranslationCompletionDTO>                    02
      saveOverride(string $code, string $key, string $raw, ?int $userId): Result<TranslationState>   02
      rows(string $code, ?string $search, bool $missingOnly): Result<list<TranslationRowDTO>>        04
```

HTTP controllers depend only on `TranslationServiceInterface`, and Filament pages only on `TranslationEditorServiceInterface`. Both services build on `TranslationResolver`, so a bundle and a completion count can never disagree about what counts as translated.

### 4.2 Contract

Backend first: `composer openapi` (Scramble), then `npm run orval:file` in `client/`, or run both with `npm run api:generate`. Never hand-edit `api.json` or the Orval output (`CONTRACT-api-json-orval-workflow`, `repo-generated-client-first`).

### 4.3 Client

| Piece | Where |
|---|---|
| Provider | `src/providers/contexts/translation-provider.tsx`, mounted in `App.tsx` inside `QueryClientProvider`, above `AuthProvider` |
| Hook | `useTranslation()` in `src/hooks/`, returning `{ t, locale, locales, setLocale, error }` |
| API | The generated Orval hooks only, with no hand-written fetch |
| Selector | `src/components/features/LanguageSelector/`, rendered in the header account area and the mobile `MenuDrawer` |

### 4.4 Plan decision P1: an English snapshot in the client (fills a gap the spec left open)

The spec makes English code-owned on the backend, but the client has nothing to render before the first bundle arrives. Migrated labels would then flash raw keys on every page load, and every existing component test would need an API mock.

**Decision:**
- Story 01 adds `php artisan translations:export-source`, which writes the flattened English catalogue to `client/src/i18n/en.generated.json`. The file is committed, the way generated Orval output is. A `--check` flag exits non-zero when the file is out of date.
- Story 06 uses the snapshot as the `en` bundle and as the default context value, so English renders with no request and no provider in tests.
- Any key added to `ui.php` means re-running the export in the same commit. Wiring `--check` into CI becomes a `needs-triage` follow-up.

**Other side:** a second generated artefact to keep in sync. With a non-English stored choice, English shows briefly before the bundle loads. That is better than raw keys or a blocking spinner.

### 4.5 Checks before each push

The full list is in `docs/agents/local-environment.md`. Read it in a fresh worktree before running anything.

- **Backend:**
  - Run the targeted tests in the Docker test lane with project name `jvma659-0N`:
    - `docker compose -f docker-compose.test.yml -p jvma659-0N up -d --build`
    - `… exec -T test-runner php artisan passport:keys --force`
    - `… exec -T test-runner composer test -- tests/Feature/Translations tests/Unit/Translations`
  - Run Pint on the changed files.
  - If Docker is down, WSL PHP 8.3 can run Pint and unit tests, and DB tests must then be reported as not run.
  - No PHPStan, and no full suite.
- **Frontend:** `npm run typecheck`, ESLint on the changed files, targeted `vitest run --project unit <paths>`, the Storybook project for new stories, and `npm run build`. A `spawn EPERM` inside the sandbox is an environment limit: re-check outside it before calling it a failure. A local `.env` `NODE_ENV` can break `vite build`.
- **Environment traps:**
  - Bash halves backslashes in heredocs, so write PHP files with the Write or Edit tools.
  - Use `set -o pipefail` in piped checks.
  - Parallel Bash calls share a working directory.
  - A PostgreSQL unique violation aborts the transaction, so use an upsert, not insert-then-catch.

### 4.6 Decisions from the Project K comparison (2026-11-08)

The plan was compared with the translation system of a reference codebase, Project K. It keeps UI labels and entity content apart, as we do. Its labels are declared in code with English defaults, edited in an admin grid, served as one bundle per locale, and read in React from a context. The user accepted these decisions:

| # | Decision | Lands in | Other side |
|---|---|---|---|
| D-K1 | Separate the read side (`TranslationService`, HTTP) from the edit side (`TranslationEditorService`, Filament), with a pure `TranslationResolver` in Domain that owns the override rule | 02, 04, 05 | One more interface. A single service would mix public reads with admin needs. |
| D-K2 | A `TranslationKey` type generated from the English snapshot. `t(key: TranslationKey)` turns a key typo into a `tsc` error. | 06, 07 | Adding a key means re-exporting the snapshot, which P1 already requires. |
| D-K3 | A nullable `updated_by_user_id` on `translations`, set on every save | 02, 05 | Slightly past "ordinary timestamps", though it is still not history. |
| D-K4 | The overview opens with the Missing filter on | 04 | Someone who only wants to browse needs one click to see All. |
| D-K5 | Development builds only: `console.warn` once per key that is missing from both the bundle and the snapshot | 06 | Console noise during the incremental migration. |
| D-K6 | No copying English into the database. English stays code-owned. | — | English cannot be changed without a deploy. That is intended. |
| D-K7 | No cache yet. If one is ever added, invalidate it on save by bumping a version stamp, rather than reloading on a timer. | — | Every bundle request reads the database: one query plus a merge in PHP. |

## 5. Story sections

### TRANSLATIONS-01 (#660): English catalogue, locale list and `en` bundle API

**Requirements:** FR-01, FR-02 (English row), FR-03, FR-04 (English path), NFR-04.

1. **Migration `translation_locales`** (`id`, `code` unique `varchar(8)`, `name`, `is_active` default false, timestamps). **Insert the `en` row in the migration itself**, so every environment, including tests with `RefreshDatabase`, has it without seeding. Add a model and a factory (non-English codes only).
2. **`resources/lang/en/ui.php`**, nested by area, with the starter keys for the story 07 surfaces, taken word for word from today's UI:
   - `account.log_in` "Log In", `account.sign_up` "Sign Up", `account.nav_label` "Account", `account.status_label` "Account status", `account.checking_status` "Checking account status";
   - the `AccountMenu` items and log-out label;
   - `auth.login.*`: heading, `email` "Email", `password` "Password", submit, links;
   - `common.save`, `common.cancel`, `common.submit`, as `FormPage` shows them;
   - `library.empty.*`: the `LibraryEmptyState` defaults, if any are hard-coded.
3. **English source catalogue adapter** (Application interface plus Infrastructure implementation). It reads `ui.php` through Laravel's translator or loader, flattens to `array<string,string>` with dotted keys, and fails loudly in tests if any value is empty. It is the only reader of `ui.php` (AC-4).
4. **Locale repository and translation service, first cut:** `activeLocales()`, and `bundle(code)` for `en` only. Unknown code → `TranslationErrors::localeNotFound()` → typed 404.
5. **Routes, in this order:** `GET translations/locales`, then `GET translations/{locale}`. Add controller, Resources and Scramble annotations so the payload is typed: `messages` is `Record<string,string>`.
6. **`translations:export-source`** command and the committed `client/src/i18n/en.generated.json` (P1, §4.4).
7. **Tests:**
   - feature: locales list, `en` bundle shape, unknown locale 404, route not shadowed;
   - unit: flattening, unknown-key lookup;
   - the command's `--check` passing and failing.
8. Run `npm run api:generate`, then commit the PHP, the generated client and the snapshot together.

### TRANSLATIONS-02 (#661): overrides merged into bundles

**Requirements:** FR-04 to FR-08, NFR-01, NFR-02, NFR-09.

1. **Migration `translations`:** FK `translation_locale_id` with cascade on delete, `key` `varchar(191)`, `value` `text`, nullable `updated_by_user_id` (FK to `users`, null on delete; D-K3), timestamps, and unique (`translation_locale_id`, `key`). Add model and factory.
2. **`TranslationResolver`** (Domain, pure, with no IO; D-K1): `merge(source, overrides)`, `state(override)` and `completion(source, overrides)`. A key counts as translated when its override is non-empty and the key exists in English, so orphans are dropped (FR-07). `percent` is rounded down to an integer, so 1 of 4 is "25%"; record that rule in the DTO docblock. Unit-test it without the database.
3. **`TranslationOverrideRepositoryInterface`** and its Eloquent implementation:
   - `overridesFor(localeId): array<string,string>`, in one query;
   - `upsert(localeId, key, value, ?userId)`, using `upsert()` on the unique index;
   - `delete(localeId, key)`.
4. **Read side:** `TranslationService::bundle(code)` resolves an active locale (an inactive one gets the same 404 as an unknown one), loads the overrides once, and returns `TranslationResolver::merge`.
5. **Edit side:** a new `TranslationEditorServiceInterface` and service, which Filament will use. It does not extend `TranslationService`.
   - `saveOverride(code, key, raw, ?userId)`: trim, and delete when the result is empty. More than 2,000 characters, `en`, an unknown key or an inactive locale return a typed failure with no write. On success it returns the new `TranslationState`.
   - `completion(code)`: delegates to the resolver.
6. **Tests:**
   - unit: resolver rules, including orphan exclusion (AC-5);
   - unit: save rules (AC-3, AC-4), including `updated_by_user_id` being stored;
   - feature: the merged bundle (AC-1) and an inactive locale (AC-2);
   - query count with `DB::enableQueryLog()`, equal for 3 and 30 keys (AC-6).
7. The contract only changes if the error shapes change. Regenerate if `api.json` differs.

### TRANSLATIONS-03 (#662): permissions and Filament locale management

**Requirements:** FR-02, FR-11, NFR-03.

1. **Permissions:** a `translations` group in `config/permission-catalog.php` with `translations.view`, `translations.update` and `locales.manage`, plus the `PanelPermission` cases. Check that the Role editor shows the group (AC-1). That is a Filament feature test on the role form.
2. **`TranslationLocaleResource`:**
   - list with code, name and an active toggle;
   - create (code and name);
   - edit (name and active);
   - no delete action.
   Navigation group: add a `Localisation` case to `AdminNavigationGroup` if none fits. Use a `TranslationLocaleAdminPolicy` extending `PanelResourcePolicy`, with `locales.manage` for view and update and `null` for delete.
3. **English guard in the model or service:** the `en` row cannot be deactivated, and its code cannot change. Enforce it in the service or repository, not only by hiding the toggle (AC-3). In the UI, disable the toggle and the code field for `en`.
4. **Validation:** `regex:/^[a-z]{2}$/`, unique (AC-4).
5. **Tests:**
   - Livewire/Filament feature tests for create, toggle and the 403 for a dashboard-only user (AC-2, AC-5);
   - a crafted update on `en` is refused (AC-3).

### TRANSLATIONS-04 (#663): Filament translation overview

**Requirements:** FR-06 to FR-09, FR-11, NFR-01.

1. **Custom page `TranslationOverview`** (slug `translations`). `#[Url]` properties `locale`, `search`, and `filter` (`all|missing`, **default `missing`**, D-K4). `canAccess()` → `PanelActor::may(PanelPermission::TranslationsView)`. The page depends only on `TranslationEditorServiceInterface`.
2. **Rows** come from `TranslationEditorServiceInterface::rows(code, search, missingOnly): list<TranslationRowDTO>`. It is built from the catalogue plus one overrides query. Search and filter run in PHP on that list, which is fine at a few hundred keys (assumption in the requirements, §8).
   - Render with a Filament table on custom data (`Table::records()` in Filament ≥ 4) with search and a select filter.
   - If the records API cannot drive search and filter cleanly, fall back to a Blade view with a plain table, and record that in the PR.
3. **Header:** locale select (active non-English locales only, defaulting to the first) and the subheading `N of M translated · P% complete` from `completion()`.
4. **Badges:** `Translated` (success) and `Missing` (gray), with text always shown and colour never the only cue.
5. **Row action:** a link to the editor (`?locale=&key=`), added once story 05 exists. Until then, show the key as plain text, so the story is complete on its own.
6. **Tests:**
   - Livewire tests for: the page opening on the Missing filter, all rows after switching to All, search on each field, the `1 of 4 translated · 25% complete` line with an orphan present, and a 403 without permission;
   - query count is fixed for 3 vs 30 keys.

### TRANSLATIONS-05 (#664): Filament translation editor

**Requirements:** FR-05, FR-10, FR-11, NFR-02, NFR-03, NFR-09.

1. **Custom page `TranslationEditor`** (slug `translations/edit`). Use `#[Url]` `locale` and `key` query parameters (keys contain dots, so keep them out of path segments). `canAccess()` needs `translations.view`, and the Save action needs `translations.update`.
2. **Form:**
   - a searchable key select (source keys);
   - read-only key and English (a text entry, not an input);
   - a textarea with `maxLength(2000)` and the helper "Leave empty to use English";
   - a live fallback preview when the field is empty;
   - one `Save translation` action.
   The success notification reads "Saved. Live on the next page load."
3. **Save** calls `TranslationEditorServiceInterface::saveOverride(code, key, raw, auth()->id())`, which records the translator in `updated_by_user_id` (D-K3). A failure Result becomes a danger notification with no write:
   - for a deactivated locale, "This language is no longer active. Choose an active language.";
   - for a removed key, "This key no longer exists" and the key list is refreshed.
4. **Read-only mode:** without `translations.update`, the textarea is disabled, the action is hidden, and a direct Livewire call returns 403.
5. Wire the story 04 row link to this page.
6. **Tests:**
   - save makes the next `GET /v1/translations/da` include the value, stores the translator's id, and the overview shows Translated;
   - a blank save restores English and Missing;
   - deactivated locale and removed key write nothing;
   - view-only user;
   - `<b>x</b>` round-trips as text.

### TRANSLATIONS-06 (#665): React provider and header language selector

**Requirements:** FR-12 to FR-15, NFR-05 to NFR-07.

1. **Provider:**
   - Initial `locale` = the stored choice (`localStorage` key `jvma.locale`, try/catch) → the browser base language (`navigator.languages[0].split('-')[0]`) → `en`. Once the locale list arrives, an inactive choice falls back to `en`.
   - The `en` bundle comes from `en.generated.json` (no request).
   - Other bundles use the generated query hook with `placeholderData: keepPreviousData`, so labels never blank (AC-3).
   - On error: keep the last bundle and expose `error`. A small polite `role="status"` notice near the selector says "Couldn't load that language. Showing <current>."
2. **`t(key)`:**
   - **Typed keys (D-K2).** Add `src/i18n/keys.ts` exporting `type TranslationKey = keyof typeof en`, built from `en.generated.json`. `t(key: TranslationKey)` makes a typo fail `npm run typecheck`. Callers that receive a key at runtime cast it once at the boundary.
   - **Lookup order:** `bundle[key] ?? enSnapshot[key] ?? key`.
   - **Dev warning (D-K5).** In development builds only (`import.meta.env.DEV`), `console.warn` once per key that is missing from both. Production stays silent.
   - **Default context.** With no provider, the context uses the English snapshot, so existing tests keep working.
3. **Effect:** `document.documentElement.lang = locale`.
4. **`LanguageSelector`:** a native `<select>` with a visible or visually-hidden "Language" label and endonym option labels ("Dansk"). It renders nothing when only one locale is active (AC-5). Put it in the header account `<ul>` (desktop) and the `MenuDrawer` (mobile).
5. **Tests (Vitest with MSW):**
   - resolution branches;
   - switch without reload, plus persistence and `lang`;
   - keep the previous bundle while loading and on a 500;
   - a key missing from both the bundle and the snapshot returns the key and warns only in dev, once;
   - a `// @ts-expect-error` test proves that an unknown key literal does not type-check;
   - hidden with a single locale.
6. **Stories:** `LanguageSelector` hidden, two locales, error. Update the header stories with the MSW handlers that Orval generates.
7. Screenshots at desktop, tablet and mobile with two locales active.

### TRANSLATIONS-07 (#666): representative surfaces on translation keys

**Requirements:** FR-16, NFR-07, plus end-to-end proof of FR-04, FR-05 and FR-14.

1. **Replace the literals with `t()`** in:
   - `Header` (account area and aria labels);
   - `AccountMenu`;
   - the `Login` form (heading, labels, submit, links);
   - `FormPage` submit and cancel defaults;
   - the `LibraryEmptyState` defaults, if present.
   Leave the header and footer menu links alone (BUILDER-CMS #588).
2. Add any missing keys to `ui.php`, run `translations:export-source`, and check `--check` passes.
3. With `en`, the existing tests and stories pass with unchanged visible text (AC-1).
4. **End-to-end demo for the PR:**
   1. Run the lane or local stack.
   2. Create `da` in Filament.
   3. Translate `account.log_in` and `auth.login.submit`.
   4. Switch the header to Dansk and take a screenshot (AC-2).
   5. Edit one value.
   6. Reload and take a screenshot (AC-3).
5. Mark FR-16 and NFR-07 Delivered in the requirements traceability table, and list the added keys in the PR (AC-4).

## 6. Epic close-out

1. Merge `origin/develop` into the integration branch one last time and run the full set of checks from §4.5 for both apps.
2. Open the epic PR `feature/issue-659-translations` → `develop` with `create-pull-request`:
   - an AC table covering the epic ACs and linking each story PR;
   - `Refs #659` plus every story issue;
   - follow-ups (the `--check` CI wiring, and per-feature string migration issues).
3. Leave `docs/translation-management/` in place until the epic merges. Then decide whether these docs still add value, or whether the code and tests now show the rules on their own (`requirements.md` §9).

## 7. Run prompt

Paste the block below into a **new session** whose working folder is `.claude/worktrees/translations` (branch `feature/issue-659-translations`), running Opus at effort high or above. That session orchestrates and does not write product code itself.

````text
You are the orchestrator for epic #659 TRANSLATIONS in av3000/japanese-vma. Your working folder is the
worktree on branch feature/issue-659-translations (the integration branch). Read, in this order:
docs/translation-management/implementation-plan.md (the plan; §2 branch model, §3 the loop),
docs/translation-management/requirements.md, and `gh issue view 659` plus each story issue as you reach it.
gh is at "/c/Program Files/GitHub CLI/gh.exe".

Goal: deliver TRANSLATIONS-01..07 (#660..#666) one at a time, in key order, each as its own PR into
feature/issue-659-translations, squash-merged with subject "TRANSLATIONS-0N: <outcome> (#<pr>)". Then open
the epic PR into develop (plan §6) and stop for me to review it. Do not merge the epic PR.

Before story 01: git fetch origin, merge origin/develop into this branch (merge, never rebase), push.
Repeat before each later story if develop moved.

For each story N, run the loop in plan §3:
1. Start an implementation session with mcp__ccd_session__start_session: initiation "user_asked",
   context "fresh", model "claude-sonnet-5-5", effort "high", use_worktree true,
   source_branch "feature/issue-659-translations", branch_name "translations-0N", name "tr-0N-impl",
   wake_when_done true. Brief: the story key and issue number, "read the issue, implementation-plan.md §4
   and §5 TRANSLATIONS-0N, and the scoped AGENTS.md; first write your story plan mapped to every AC, then
   build it test-first; run plan §4.5 checks; commit as 'TRANSLATIONS-0N: <outcome>' with body 'Refs #<issue>'
   and no attribution; push; open the PR with create-pull-request but with base feature/issue-659-translations;
   report the PR number and branch name, and any check you could not run".
2. When it reports, verify the PR exists, targets the integration branch, and every commit subject starts
   with TRANSLATIONS-0N. Then start a review session: same tool, model omitted (inherit), effort "high",
   use_worktree true, source_branch = the story branch, name "tr-0N-review", wake_when_done true. Brief:
   "read-only review of PR #<pr>: run review-branch for the PR and post it with post-review-to-pr, then run
   /code-review high <pr> --comment. Change no code. Report the finding counts by criticality and the verdict."
3. Then SendMessage to tr-0N-impl: "Run /address-pr-review <pr>." and forward my standing approval below,
   quoting it.
4. When it reports, check the PR: no unresolved Critical threads, fix commit prefixed TRANSLATIONS-0N, CI
   green if CI ran. Squash-merge (plan §2), close the story issue with a comment linking the PR, git pull
   --ff-only here, and continue with the next story.

Never archive, stop or delete the sessions you start. I will read them later. Do not write product code
yourself. If a session is stuck on the same failure twice, a check cannot run, a Critical finding is
declined, or a story needs a scope change against its issue, stop and ask me with a short summary.

My standing approval for this run (forward it verbatim when relevant): "For epic #659 story PRs, you may
post the review-branch review without asking, and in address-pr-review you may proceed past the table
without my confirmation: fix every Critical and Warning that holds up, decline Suggestions you judge not
worth it with a stated reason, file out-of-scope requests as needs-triage issues, then push, reply and
resolve. Squash-merging story PRs into feature/issue-659-translations is approved. Merging into develop
is not."
````
