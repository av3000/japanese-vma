# Catalogues and Saved Lists — Current to Target

> **Status:** Migration map; compatibility behavior remains explicit
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Implementers, planners, and reviewers

## Flow Comparison

| Concern | Current | Target | Completion signal |
|---|---|---|---|
| Catalogue list/detail | v1 backend and modern frontend routes exist. | Keep generated clients and focused query/mapping modules. | Active catalogue routes use no legacy list read. |
| Create/update/delete | v1 services, policies, resources, clients, and tests exist. | Keep all orchestration and cleanup behind the application boundary. | No duplicate legacy mutation caller remains. |
| Item membership | Generated v1 add/remove and for-item reads are established. | Share one feature boundary across article and Japanese detail widgets. | All supported detail routes use the shared module with focused tests. |
| Learned state | Viewer catalogue state batches known/saved membership. | Preserve server-owned state derivation and avoid per-row duplicate calls. | Lists render learned state from batched/query-owned data. |
| Legacy identity | Old numeric list routes use a temporary compatibility adapter. | Add a small v1 numeric-ID-to-catalogue identity contract. | Generated compatibility client replaces raw legacy lookup. |
| SavedList code | Old names/components remain as transitional debt. | Rehome active item renderers under catalogue-oriented modules and remove dead routes. | Import search proves no active dependency on removed SavedList routes. |
| PDF | Kanji/word v1; radical/sentence legacy. | Add v1 service-backed exports only when required. | Supported routes, renderer services, generated clients, and tests align. |

## Migration Constraints

- Preserve legacy navigation until numeric identity resolution is v1-backed.
- Do not change list-type numeric wire values silently.
- Keep ownership and publicity behavior stable.
- Prefer generated v1 item clients over wrappers in generic action modules.
- Keep for-item reads and writes behind `client/src/api/catalogues/cataloguesForItem.ts`.
- Do not delete legacy routes in the same slice that first introduces their replacement unless callers are already proven migrated.

## Leading Follow-Up

The next compatibility slice is a focused v1 legacy-ID resolver followed by migration of `client/src/api/catalogues/legacyCatalogues.ts`. SavedList route deletion and renderer rehoming should follow only after redirect and import verification.

## Evidence

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `client/AGENTS.md`
- `processor-api/tests/Feature/Catalogues/`
- `client/src/api/catalogues/`
