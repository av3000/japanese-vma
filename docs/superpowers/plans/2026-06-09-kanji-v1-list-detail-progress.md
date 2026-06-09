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
- [ ] Phase 2: Tasks 2-4 - Implement backend Kanji v1 contract/source schema
- [ ] Phase 3: Task 5 - Regenerate OpenAPI and Orval
- [ ] Phase 4: Tasks 6-8 - Migrate frontend Kanji list/detail
- [ ] Phase 5: Task 9 - Final verification

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

Status: pending

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

### Phase 3: Generated Contract

Status: pending

Expected commands:
- `composer openapi`
- `npm run orval:file`

### Phase 4: Frontend Migration

Status: pending

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

### Phase 5: Verification

Status: pending

Expected commands:
- Backend Kanji feature test
- Frontend Kanji tests
- Frontend typecheck
- Generated contract diff review
