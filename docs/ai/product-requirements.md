# Product Requirements Baseline

> **Status:** Maintainable baseline, not an exhaustive product specification
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Product-minded engineers, reviewers, and AI-assisted contributors

## Purpose and Scope

Japanese VMA helps people read, organize, create, and discuss Japanese learning material. This baseline names the capabilities evident in the repository and separates them from migration targets.

It does not define a new product roadmap, visual redesign, monetization model, or service decomposition.

## Actors

| Actor | Description |
|---|---|
| Visitor | Unauthenticated person browsing public content and study material. |
| Learner | Authenticated user organizing and interacting with study material. |
| Contributor | Authenticated user creating articles, catalogues, sentences, or community content where supported. |
| Owner | User allowed to modify or delete a specific owned entity. |
| Administrator | User with elevated role-management and moderation responsibilities. |
| Operator | Maintainer responsible for deploy, worker, queue, database, and service health. |

## Functional Requirements

### Authentication and identity

| ID | Requirement | State |
|---|---|---|
| FR-AUTH-001 | A visitor can register and log in. | Verified current |
| FR-AUTH-002 | An authenticated user can log out and retrieve the current session identity. | Verified current |
| FR-AUTH-003 | Authenticated users can view user profiles and lists where access rules allow. | Verified current |
| FR-AUTH-004 | Administrators can inspect users and manage roles through protected backend operations. | Verified current; UI breadth not fully assessed |

### Discovery and navigation

| ID | Requirement | State |
|---|---|---|
| FR-DISC-001 | A visitor can browse article, catalogue, kanji, radical, word, and sentence lists. | Verified current |
| FR-DISC-002 | List surfaces can search, filter, sort, and paginate according to the resource contract. | Verified current with resource-specific variation |
| FR-DISC-003 | A visitor can open public detail routes for core learning content. | Verified current |
| FR-DISC-004 | Legacy list URLs resolve or redirect without losing reachable catalogue content. | Verified current compatibility; target is v1-backed resolution |

### Articles

| ID | Requirement | State |
|---|---|---|
| FR-ART-001 | A visitor can list and read publicly visible articles. | Verified current |
| FR-ART-002 | An authenticated contributor can create an article with validated content and tags. | Verified current |
| FR-ART-003 | An authorized owner can update or delete an article. | Verified current |
| FR-ART-004 | Article details can expose related Japanese study data, engagement, and processing state. | Verified current |
| FR-ART-005 | Administrative status review and pending-article flows use complete v1 contracts. | Target; registered routes require completion verification |

See the [Articles packet](../feature-artifacts/articles/abstract.md).

### Catalogues and saved lists

| ID | Requirement | State |
|---|---|---|
| FR-CAT-001 | Users can browse public catalogues and their contents. | Verified current |
| FR-CAT-002 | An authenticated user can create, update, and delete an owned catalogue. | Verified current |
| FR-CAT-003 | An authenticated owner can add or remove compatible study items. | Verified current |
| FR-CAT-004 | The UI can list owned catalogues relevant to an item and show membership. | Verified current |
| FR-CAT-005 | Legacy saved-list routes preserve navigation while active callers migrate. | Legacy compatibility requirement |
| FR-CAT-006 | Saved-list surfaces converge on catalogue-v1 contracts and generated clients. | Target |

See the [Catalogues packet](../feature-artifacts/catalogues-and-saved-lists/abstract.md).

### Japanese study material

| ID | Requirement | State |
|---|---|---|
| FR-JP-001 | Users can list and inspect kanji. | Verified current |
| FR-JP-002 | Users can list and inspect radicals. | Verified current |
| FR-JP-003 | Users can list and inspect words. | Verified current |
| FR-JP-004 | Users can list and inspect sentences. | Verified current |
| FR-JP-005 | Resource lists support relevant search, sort, JLPT, grade, or relationship filters. | Verified current with resource-specific variation |
| FR-JP-006 | Detail routes expose related material through explicit, typed contracts. | Mixed current/target |
| FR-JP-007 | Users can organize supported study items in catalogues. | Verified current |

See the [Japanese Study Material packet](../feature-artifacts/japanese-study-material/abstract.md).

### Community and engagement

| ID | Requirement | State |
|---|---|---|
| FR-ENG-001 | Users can browse community posts and post details. | Verified current through legacy routes |
| FR-ENG-002 | Authenticated contributors can create and edit community posts where authorized. | Verified current through legacy routes |
| FR-ENG-003 | Users can read article and catalogue comments. | Verified current v1 |
| FR-ENG-004 | Authenticated users can create a comment for a supported entity. | Verified current v1 |
| FR-ENG-005 | Users can like supported entities and see engagement state. | Mixed v1/legacy current state |
| FR-ENG-006 | Comment update/delete and post interactions converge on generic, typed v1 contracts. | Target |

See the [Community and Engagement packet](../feature-artifacts/community-and-engagement/abstract.md).

### Processing and output

| ID | Requirement | State |
|---|---|---|
| FR-PROC-001 | Article create/update dispatches Japanese-language processing when relevant text changes. | Verified current |
| FR-PROC-002 | The user can observe processing state without blocking the initial write request. | Verified current architecture; end-to-end runtime not inspected |
| FR-PDF-001 | An authorized user can export article kanji or word study data as PDF. | Verified current v1 |
| FR-PDF-002 | An authorized user can export supported catalogue kanji or word data as PDF. | Verified current v1 |
| FR-PDF-003 | Radical and sentence catalogue exports move to supported v1 services before legacy retirement. | Target |

## Business Rules

- Private content is visible only according to viewer and ownership policy.
- Article and catalogue writes require authenticated identity and appropriate authorization.
- Catalogue items must match the catalogue type and cannot be duplicated.
- Generic engagement writes use a recognized object-template type and coherent entity identifiers.
- Article processing is asynchronous; initial content creation is distinct from completed extraction.
- Generated API clients are derived artifacts and must follow the backend schema source.
- Legacy routes remain until caller migration and retirement verification are explicit.

## Non-Functional Requirements

| ID | Requirement | State and evidence |
|---|---|---|
| NFR-ACC-001 | Core navigation, forms, dialogs, and study content should remain keyboard and screen-reader usable. | Target; baseline accessibility audit not performed |
| NFR-PERF-001 | Route code should avoid unnecessary startup work through lazy loading and feature-scoped data fetching. | Target with current route precedents |
| NFR-PERF-002 | High-volume list data should use server pagination and React Query rather than page-owned accumulation. | Target with article/catalogue precedents |
| NFR-REL-001 | Queue work must be observable, retry-safe, and compatible across web/worker deployments. | Target; partial operational evidence |
| NFR-REL-002 | Database-backed backend verification must use the isolated Docker test lane. | Mandatory working rule |
| NFR-SEC-001 | Authentication and authorization must be enforced at backend boundaries. | Verified current in migrated modules; legacy audit remains |
| NFR-SEC-002 | Validation rejects malformed payloads before use-case orchestration. | Verified current v1 pattern |
| NFR-MNT-001 | New or migrated backend work follows explicit HTTP, application, domain, and persistence boundaries. | Target with current modules |
| NFR-MNT-002 | New or migrated frontend work uses typed feature/query boundaries and generated clients where usable. | Target with current precedents |
| NFR-CON-001 | Public API schema and generated TypeScript types remain synchronized. | Mandatory target; drift risk documented |
| NFR-OBS-001 | Web, worker, queue, processing, and deployment health have actionable verification signals. | Target; provider-level coverage open |
| NFR-DOC-001 | Major architecture and feature claims remain traceable to repository evidence. | Implemented by this baseline |

## Out of Scope for This Baseline

- A full UX specification for every route.
- A commitment to microservices, CQRS, or event sourcing.
- Immediate deletion of legacy endpoints.
- New product behavior or API contracts.
- Claims about live service health, capacity, or production data freshness.

## Traceability

The [evidence manifest](./evidence-manifest.md) maps representative requirements and architecture claims to implementation sources. Each feature packet expands behavior, mutations, and migration completion signals.
