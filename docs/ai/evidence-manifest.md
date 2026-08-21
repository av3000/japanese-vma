# Evidence Manifest

> **Status:** Baseline; current and target claims are labelled
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Maintainers, reviewers, and AI-assisted contributors

## Purpose

This manifest makes the documentation baseline auditable. It records which repository sources support important product and architecture claims, where evidence conflicts, and which facts still need runtime verification.

It is not a generated inventory of every file. It is a curated map of the sources needed to understand and safely change the system.

## Evidence States

| State | Use |
|---|---|
| **Verified current** | Directly visible in code, configuration, tests, or repository documentation. |
| **Inferred current** | Supported by several sources but not exercised during this review. |
| **Target** | Required by contributor guidance, accepted context, or migration plans. |
| **Legacy/debt** | Present in the repository but explicitly not a pattern to extend. |
| **Open question** | Evidence is incomplete, conflicting, or requires a live-system check. |

## Source Authority

Authority depends on the claim:

1. Code and configuration describe implemented structure.
2. Tests describe behavior only within their exercised scope.
3. `AGENTS.md`, `client/AGENTS.md`, and `processor-api/AGENTS.md` define mandatory working constraints and preferred patterns.
4. `CONTEXT.md` defines accepted architecture language and direction.
5. Focused plans under `docs/` describe target work until implementation evidence proves completion.
6. READMEs orient contributors but are cross-checked against dependency manifests and configuration for version or runtime claims.

This review inspected repository evidence only. It did not call the live frontend, Render service, GCP worker, Upstash Redis, or external Japanese-data providers.

## Source Inventory

### Repository guidance and product orientation

| Source | What it supports |
|---|---|
| `AGENTS.md` | Monorepo shape, cross-system deployment, generated-contract workflow, verification lanes, and scope rules. |
| `client/AGENTS.md` | Preferred React route, query, generated-client, form, modal, and migration boundaries. |
| `processor-api/AGENTS.md` | Preferred Laravel v1 layers, DTO/resource/error conventions, authorization, schema generation, and Docker test lane. |
| `CONTEXT.md` | Robust modular monolith target, module-seams-first audit, and explicit rejection of infrastructure-first scalability work. |
| `README.md` | Product capabilities, repository map, local setup, commands, and high-level runtime orientation. |
| `client/README.md` | Frontend setup and contributor workflow. |
| `processor-api/README.md` | Backend setup, runtime, and test workflow. |

### Frontend implementation

| Source | What it supports |
|---|---|
| `client/src/routes/routes.tsx` | Public, protected, legacy-redirect, study-resource, community, and dashboard route surfaces. |
| `client/src/api/articles/hooks/useInfiniteArticles.ts` | React Query infinite-list precedent for articles. |
| `client/src/api/articles/details.ts` | Article detail mapping and query/mutation boundary. |
| `client/src/api/catalogues/hooks/useInfiniteCatalogues.ts` | React Query infinite-list precedent for catalogues. |
| `client/src/api/catalogues/cataloguesForItem.ts` | Generated v1 catalogue-for-item reads and item mutations. |
| `client/src/api/catalogues/legacyCatalogues.ts` | Temporary legacy catalogue identity compatibility debt. |
| `client/src/routes/community/PostsList/index.tsx` | Legacy community list data access. |
| `client/src/routes/community/PostDetails/index.tsx` | Legacy post detail and engagement data access. |
| `client/src/routes/japanese/` | Current kanji, radical, word, and sentence route implementations and migration state. |
| `client/src/api/generated/` | Orval-generated API clients and models; generated files are outputs, not hand-edit targets. |

### Backend implementation

| Source | What it supports |
|---|---|
| `processor-api/routes/api_v1.php` | Current public, authenticated, and admin v1 route surface. |
| `processor-api/routes/api.php` | Coexisting legacy article, list, sentence, post, comment, like, and search routes. |
| `processor-api/app/Http/v1/` | v1 controllers, requests, and resources. |
| `processor-api/app/Application/` | Use-case orchestration, policies, actions, services, jobs, and repository interfaces. |
| `processor-api/app/Domain/` | Domain DTOs, models, value objects, enums, errors, and query criteria. |
| `processor-api/app/Infrastructure/Persistence/` | Eloquent persistence models, repositories, mappers, and query builders. |
| `processor-api/app/Shared/Http/TypedResults.php` | Shared v1 HTTP success and error response boundary. |
| `processor-api/app/Shared/Results/Result.php` | Application-service success/failure result boundary. |

### Contracts, tests, and runtime

| Source | What it supports |
|---|---|
| `processor-api/api.json` | Generated OpenAPI artifact consumed by frontend generation. |
| `client/orval/orval.config.js` | Orval input/output and generated-client configuration. |
| `processor-api/composer.json` | Installed backend framework and package constraints. |
| `client/package.json` | Frontend framework, library, and command definitions. |
| `processor-api/tests/Feature/Articles/` | v1 article list, detail, create, update, delete, processing, and PDF behavior. |
| `processor-api/tests/Feature/Catalogues/` | v1 catalogue list, detail, item mutation, update/delete, picker, and PDF behavior. |
| `processor-api/tests/Feature/JapaneseMaterial/` | v1 kanji, radical, word, and sentence list/detail contracts. |
| `processor-api/tests/Feature/Comments/` | v1 article/catalogue comment reads and generic comment creation. |
| `client/src/api/articles/hooks/useInfiniteArticles.test.ts` | Article pagination/query behavior. |
| `client/src/api/catalogues/cataloguesForItem.test.ts` | Catalogue picker and item-mutation frontend behavior. |
| `.github/workflows/frontend-ci.yml` | Frontend verification and GitHub-hosted deployment flow. |
| `.gitlab-ci.yml` | Backend image, GCP worker, verification, and Render deployment orchestration. |
| `processor-api/docker-compose.yml` | Local backend, database, Redis, test runner, and service topology. |
| `client/docker-compose.yml` | Local frontend container topology. |

### Migration and focused documentation

| Source | What it supports |
|---|---|
| `docs/legacy-v1-migration/backend-frontend-issue-backlog.md` | Approved migration slicing, dependency order, non-goals, and remaining legacy surfaces. |
| `docs/agents/domain.md` | Current guidance lookup order and single-context repository assumption. |
| `docs/agents/issue-tracker.md` | GitHub issue-tracker conventions. |
| `docs/agents/triage-labels.md` | Default issue-triage vocabulary. |

## Claim-to-Source Map

| Claim ID | Claim | State | Primary evidence |
|---|---|---|---|
| ARCH-001 | Japanese VMA is a React client plus Laravel API in one repository. | Verified current | `README.md`, `client/package.json`, `processor-api/composer.json` |
| ARCH-002 | The backend is moving toward a layered domain-oriented modular monolith. | Verified current + target | `processor-api/app/Domain/`, `processor-api/app/Application/`, `processor-api/app/Http/v1/`, `CONTEXT.md` |
| ARCH-003 | Legacy and v1 HTTP routes coexist and must be migrated incrementally. | Verified current | `processor-api/routes/api.php`, `processor-api/routes/api_v1.php` |
| ARCH-004 | The target frontend flow uses thin routes, feature composition, React Query, and generated clients. | Target with current precedents | `client/AGENTS.md`, `client/src/api/articles/`, `client/src/api/catalogues/` |
| ARCH-005 | Generated contract changes flow from backend schema generation to OpenAPI and then Orval. | Target and operational rule | `AGENTS.md`, `processor-api/api.json`, `client/orval/orval.config.js` |
| ARCH-006 | Frontend deployment uses GitHub Actions; backend and worker deployment spans GitLab CI, Render, GCP, and Redis. | Verified configuration; live state unverified | `AGENTS.md`, `.github/workflows/frontend-ci.yml`, `.gitlab-ci.yml` |
| ARCH-007 | Production web and queue-worker runtimes are intentionally separated. | Verified configuration; live state unverified | `AGENTS.md`, `.gitlab-ci.yml`, `processor-api/docker-compose.yml` |
| REQ-001 | Anonymous users can browse articles and Japanese study resources. | Verified current | `processor-api/routes/api_v1.php`, `client/src/routes/routes.tsx` |
| REQ-002 | Authenticated users can create, update, and delete articles and catalogues through v1 routes. | Verified current | `processor-api/routes/api_v1.php`, article and catalogue feature tests |
| REQ-003 | Users can collect study items in catalogues and inspect catalogue membership. | Verified current | `processor-api/app/Application/Catalogues/`, `client/src/api/catalogues/cataloguesForItem.ts` |
| REQ-004 | Articles can trigger asynchronous kanji and word processing. | Verified current | `processor-api/app/Application/Articles/Jobs/`, `processor-api/tests/Feature/Articles/StoreArticleTest.php` |
| REQ-005 | Article and supported catalogue study data can be exported as PDFs. | Verified current | v1 PDF routes and article/catalogue PDF feature tests |
| REQ-006 | Community posts remain primarily on legacy endpoints. | Legacy/debt | `processor-api/routes/api.php`, `client/src/routes/community/` |
| FEAT-ART-001 | Article details may include words, kanji, engagement data, and processing status. | Verified current | `processor-api/tests/Feature/Articles/ShowArticleTest.php`, `processor-api/app/Http/v1/Articles/Resources/` |
| FEAT-CAT-001 | Catalogue item add/remove requires authentication and ownership checks. | Verified current | `processor-api/routes/api_v1.php`, `processor-api/app/Application/Catalogues/Policies/CataloguePolicy.php`, `processor-api/tests/Feature/Catalogues/CatalogueItemMutationTest.php` |
| FEAT-JP-001 | Kanji, radical, word, and sentence resources expose public v1 list/detail routes. | Verified current | `processor-api/routes/api_v1.php`, `processor-api/tests/Feature/JapaneseMaterial/` |
| FEAT-ENG-001 | Comment reads are resource-specific while the v1 comment write is entity-generic. | Verified current | `processor-api/routes/api_v1.php`, `processor-api/app/Http/v1/Comments/Requests/StoreCommentRequest.php` |
| FEAT-ENG-002 | Likes use a shared object-template identifier at the v1 boundary. | Verified current | `processor-api/app/Domain/Shared/Enums/ObjectTemplateType.php`, `processor-api/app/Http/v1/Engagement/Likes/` |

## Conflict Register

### CONFLICT-001: Backend framework version

- **Sources:** `README.md`, `processor-api/composer.json`, `processor-api/AGENTS.md`
- **Conflict:** The root README describes Laravel 11, while the dependency manifest and backend guidance describe Laravel 12.
- **Baseline treatment:** Use the dependency manifest as implementation authority and record Laravel 12 in technical documents. Keep the README discrepancy visible until its owning change updates it.
- **Resolution check:** Compare the installed lockfile and runtime `php artisan --version` in the backend container.

### CONFLICT-002: Route availability versus implementation completeness

- **Sources:** `processor-api/routes/api_v1.php`, v1 controllers, migration backlog
- **Conflict:** Some v1 routes are registered beside comments marking controller behavior as incomplete, while corresponding legacy endpoints remain active.
- **Baseline treatment:** A registered route is not treated as a completed capability without controller and test evidence.
- **Resolution check:** Inspect the named controller method and its focused feature tests.

### CONFLICT-003: Generated schema versus runtime payload

- **Sources:** `processor-api/api.json`, `client/src/api/generated/`, backend resources and response annotations
- **Conflict:** The repository guidance records cases where generated nested types can drift from intended runtime shapes.
- **Baseline treatment:** Treat incorrect generated output as a backend schema-source issue first. Do not describe a generated type as runtime truth without resource/test evidence.
- **Resolution check:** Regenerate OpenAPI, inspect the affected schema, regenerate Orval sequentially, and run contract-focused tests.

### CONFLICT-004: Configured deployment versus live deployment

- **Sources:** `AGENTS.md`, `.github/workflows/frontend-ci.yml`, `.gitlab-ci.yml`
- **Conflict:** Repository configuration describes the intended live topology, but this documentation review did not inspect provider state.
- **Baseline treatment:** Label topology as verified configuration and live operation as unverified.
- **Resolution check:** Inspect the latest successful workflow/pipeline, Render service, GCP worker, and Redis health evidence.

## Open Verification Register

| Question | Why it matters | Verify at |
|---|---|---|
| Which registered v1 administrative article routes are fully implemented today? | Prevents callers from migrating to placeholder behavior. | Article controller methods plus focused admin feature tests. |
| Are all production queue jobs consumed only by the GCP worker? | Defines deployment and incident-response ownership. | GitLab deployment output, GCP Compose state, and Render process configuration. |
| Which Japanese detail routes still rely on legacy aggregate endpoints in the frontend? | Determines the next safe migration slices. | `client/src/routes/japanese/` and browser/network verification. |
| Is the OpenAPI artifact synchronized with the current backend working tree? | Generated clients are only trustworthy when the schema is current. | Run backend schema generation, inspect `processor-api/api.json`, then run Orval. |
| Are post and sentence comment updates/deletes intended to converge on generic v1 mutations? | Affects the community migration contract. | Product decision plus the migration backlog and future v1 request design. |
