# Community and Engagement — Current to Target

> **Status:** Migration map; every row reached, legacy routes retired
> **Last reviewed:** 2026-09-19
> **Evidence baseline:** Repository working tree inspected on 2026-08-18; Post retirement (RET-POST-01) reflected 2026-09-19
> **Audience:** Implementers, planners, and reviewers

## Flow Comparison

| Concern | Current | Target | Completion signal |
|---|---|---|---|
| Article/catalogue comment reads | Public v1 UUID routes with paginated resources. | Keep resource-specific reads behind typed feature hooks. | Active detail routes use generated v1 read clients. |
| Comment create | Generic authenticated v1 write. | Keep shared enum/tuple contract or replace it only through an explicit contract decision. | All supported callers use the generated model and focused mutation tests. |
| Reply reads | Include shape exists; controller records incomplete behavior. | Implement and test explicit reply inclusion semantics. | Pagination and nesting behavior are contract-tested. |
| Comment update/delete | Generic authorized v1 update/delete by comment UUID; legacy resource routes retired (RET-ART-01, RET-CAT-01, RET-SEN-01, RET-POST-01). | Reached. | `CommentMutationV1Test` pins owner/admin rules and the locked-post gate. |
| Likes | One generic v1 toggle; every resource-specific legacy like/unlike/checklike route retired. | Reached. | `LikeInstanceV1Test`; the retirement tests assert no v1 unlike route exists. |
| Posts | v1 read, write/moderation, and comment contracts with generated frontend clients; the legacy `PostController` family retired (RET-POST-01). | Reached. | `PostReadV1Test`, `PostWriteV1Test`, `LegacyPostRouteRetirementTest`, and the focused client tests under `client/src/routes/community/`. |
| Hashtags/views/downloads | Shared actions/repositories enrich migrated resources. | Keep feature orchestration local while reusing narrow engagement actions. | No generic service absorbs feature-specific business rules. |

## Migration Order

Completed in this order; kept as the record of how the slices were cut:

1. Post list/detail v1 reads and frontend read callers (POST-READ-BE-01, POST-READ-FE-01).
2. Post create/update/delete/moderation contracts and forms/actions (POST-WRITE-BE-01, POST-WRITE-FE-01).
3. Post and sentence comment reads on the generic contract (COM-PARENT-BE-01, COM-PARENT-FE-01).
4. Generic comment update/delete and reply reads for article, catalogue, sentence, and post callers (COM-CORE-BE-01, COM-CORE-FE-01).
5. Like callers onto the single toggle (LIKE-BE-01, LIKE-FE-01).
6. Legacy route retirement after active-caller verification, one family per slice, with the Post family last (RET-POST-01).

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
