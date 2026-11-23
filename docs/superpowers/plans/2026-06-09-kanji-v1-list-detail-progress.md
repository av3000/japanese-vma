# Kanji V1 List And Detail Migration Progress

Source plan: `docs/superpowers/plans/2026-06-07-kanji-v1-list-detail-migration.md`

Branch approved for implementation: current checkout.

## Status Legend

- `pending`: not started
- `in_progress`: currently being edited or verified
- `blocked`: stopped by environment, test, or unclear instruction
- `complete`: implemented and verified to the extent noted

## Phase Status

- [x] Phase 0: Create restartable progress tracker
- [x] Phase 1: Task 1 - Add backend v1 Kanji feature tests
- [x] Phase 2: Tasks 2-4 - Implement backend Kanji v1 contract/source schema
- [x] Phase 3: Task 5 - Regenerate OpenAPI and Orval
- [x] Phase 4: Tasks 6-8 - Migrate frontend Kanji list/detail
- [x] Phase 5: Task 9 - Final verification

## Detailed Task Log

### Phase 0: Restart Tracker

Status: complete

Notes:
- Created this file so a later chat can resume from the last completed phase.
- Existing unrelated worktree items must remain untouched unless the user says otherwise.

### Phase 1: Backend Feature Tests

Status: complete

Expected files:
- `processor-api/tests/Feature/JapaneseMaterial/Kanjis/KanjiV1Test.php`

Notes:
- Added `KanjiV1Test.php`; next step is running it through the Docker `test-runner` lane to confirm the expected red state.
- Ran `docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Kanjis/KanjiV1Test.php`.
- Expected red state confirmed: old response envelope/shape, missing top-level `items`, missing resource `id`, missing keyword behavior, and existing stroke-count integer bug.

### Phase 2: Backend Contract Source

Status: complete

Expected files:
- `processor-api/app/Domain/JapaneseMaterial/Kanjis/DTOs/KanjiListResultDTO.php`
- `processor-api/app/Domain/JapaneseMaterial/Kanjis/Queries/KanjiQueryCriteria.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Kanjis/Requests/IndexKanjiRequest.php`
- `processor-api/app/Infrastructure/Persistence/Repositories/KanjiRepository.php`
- `processor-api/app/Application/JapaneseMaterial/Kanjis/Interfaces/Repositories/KanjiRepositoryInterface.php`
- `processor-api/app/Application/JapaneseMaterial/Kanjis/Services/KanjiService.php`
- `processor-api/app/Application/JapaneseMaterial/Kanjis/Services/KanjiServiceInterface.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Kanjis/Resources/KanjiResource.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Kanjis/Resources/KanjiListResource.php`
- `processor-api/app/Http/v1/JapaneseMaterial/Kanjis/Controllers/KanjiController.php`

Notes:
- Backend contract files are patched.
- Root-caused the stroke range failure to numeric comparisons on the legacy `varchar(5)` `stroke_count` column inside PHPUnit; fixed persistence stroke range filters with numeric casts.
- `php -l` passed for touched PHP files.
- `vendor/bin/pint --dirty` completed.
- `docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Kanjis/KanjiV1Test.php` passed: 7 tests, 47 assertions, 1 PHPUnit deprecation.

### Phase 3: Generated Contract

Status: complete

Expected commands:
- `composer openapi`
- `npm run orval:file`

Notes:
- Starting with local/script inspection to avoid dependency downloads or Docker builds while on mobile data.
- Host `composer openapi` timed out after 120s and was not treated as a valid result.
- Used the already-running Laravel container instead: `docker compose exec laravel-app php artisan scramble:export --path=api.json`.
- Ran `npm run orval:file` from `client/`; no dependency install was needed.
- Verified `kanjiIndex`, `kanjiShow`, `KanjiIndex200`, `KanjiShow200`, `KanjiIndexParams`, and `KanjiResource` generated with usable typed Kanji shapes.
- `client/src/api/generated/**` is ignored by `client/.gitignore` in this checkout, so generated TypeScript output remains local and is not force-added.
- Re-ran `docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Kanjis/KanjiV1Test.php`; passed 7 tests, 47 assertions, 1 PHPUnit deprecation.

### Phase 4: Frontend Migration

Status: complete

Expected files:
- `client/src/api/kanjis/display.ts`
- `client/src/api/kanjis/hooks/useInfiniteKanjis.ts`
- `client/src/api/kanjis/hooks/useInfiniteKanjis.test.ts`
- `client/src/api/kanjis/details.ts`
- `client/src/api/kanjis/details.test.ts`
- `client/src/routes/japanese/KanjisList/index.tsx`
- `client/src/routes/japanese/KanjisList/SearchBarKanjis/index.tsx`
- `client/src/components/features/japanese/Kanji/KanjiItem/index.tsx`
- `client/src/routes/japanese/KanjisList/index.test.tsx`
- `client/src/routes/japanese/KanjiDetails/index.tsx`
- `client/src/routes/japanese/KanjiDetails/KanjiContent.tsx`
- `client/src/routes/japanese/KanjiDetails/index.test.tsx`

Notes:
- Added `client/src/api/kanjis` display, infinite-list, and detail adapters backed by generated v1 clients.
- Replaced legacy Kanji list class component, `@ts-nocheck`, raw `apiCall`, and legacy search endpoint with URL `keyword`/`jlpt` filters plus `useInfiniteKanjis`.
- Replaced legacy Kanji detail `apiCall` route with a thin generated-query route and `KanjiContent`.
- Detail first slice renders core Kanji fields and authenticated catalogue bookmark/known-list behavior using numeric `kanji.id`.
- Related words, sentences, and articles are intentionally absent from the first migrated detail slice.
- Updated `client/src/api/radicals/details.test.ts` because regenerated `KanjiResource` now exposes the corrected nested Kanji v1 shape.

### Phase 5: Verification

Status: complete

Expected commands:
- Backend Kanji feature test
- Frontend Kanji tests
- Frontend typecheck
- Generated contract diff review

Notes:
- `docker compose exec test-runner composer test -- tests/Feature/JapaneseMaterial/Kanjis/KanjiV1Test.php` passed: 7 tests, 47 assertions, 1 PHPUnit deprecation.
- `npm run test -- src/api/kanjis/hooks/useInfiniteKanjis.test.ts src/api/kanjis/details.test.ts src/routes/japanese/KanjisList/index.test.tsx src/routes/japanese/KanjiDetails/index.test.tsx src/api/radicals/details.test.ts` passed: 5 files, 13 tests.
- `npm run typecheck` passed.
- `npm run test` emitted a Vitest browser config deprecation warning; tests still passed.
- Full frontend build was intentionally not run to avoid heavier work while the user is on mobile data.
