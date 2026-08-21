# Community and Engagement — Current to Target

> **Status:** Migration map; post and comment work intentionally sliced
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Implementers, planners, and reviewers

## Flow Comparison

| Concern | Current | Target | Completion signal |
|---|---|---|---|
| Article/catalogue comment reads | Public v1 UUID routes with paginated resources. | Keep resource-specific reads behind typed feature hooks. | Active detail routes use generated v1 read clients. |
| Comment create | Generic authenticated v1 write. | Keep shared enum/tuple contract or replace it only through an explicit contract decision. | All supported callers use the generated model and focused mutation tests. |
| Reply reads | Include shape exists; controller records incomplete behavior. | Implement and test explicit reply inclusion semantics. | Pagination and nesting behavior are contract-tested. |
| Comment update/delete | v1 controller stubs; legacy resource routes active. | Generic authorized v1 update/delete operations. | Legacy callers are migrated and stubs become tested operations. |
| Likes | Generic v1 toggle exists beside resource-specific legacy routes. | One shared typed mutation and viewer-state contract. | Active routes contain no resource-specific legacy like calls. |
| Posts | Legacy backend and raw frontend calls. | Separate v1 read, write/moderation, and comment slices. | Each slice has backend contracts/tests and frontend query/mutation coverage. |
| Hashtags/views/downloads | Shared actions/repositories enrich migrated resources. | Keep feature orchestration local while reusing narrow engagement actions. | No generic service absorbs feature-specific business rules. |

## Migration Order

1. Complete post list/detail v1 reads and migrate frontend read callers.
2. Complete post create/update/delete/moderation contracts and migrate forms/actions.
3. Complete post comment reads/writes where they differ from the generic contract.
4. Add generic comment update/delete contracts and migrate article, catalogue, sentence, and post callers.
5. Retire legacy like/comment routes only after active-caller verification.

## Constraints

- Preserve post lock/moderation semantics during migration.
- Keep backend authorization explicit for author and admin operations.
- Do not invent string entity-type mappings when generated `ObjectTemplateType` exists.
- Keep read identity and write identity differences visible until the contract changes.
- Do not bundle all community and engagement migration into one pull request or issue.

## Evidence

- `docs/legacy-v1-migration/backend-frontend-issue-backlog.md`
- `processor-api/routes/api.php`
- `processor-api/routes/api_v1.php`
- `client/src/routes/community/`
- `processor-api/tests/Feature/Comments/`
