---
name: backend-standards
description: Citable backend rules for the processor-api/ Laravel API (v1 modular monolith, PostgreSQL, Scramble OpenAPI, Orval). Each rule has a stable ID (ARCH-, CONTRACT-, RESULT-, DATA-, TEST-), an impact level and an Incorrect/Correct example from the v1 modules. Use when writing, reviewing or refactoring code under processor-api/, when deciding where backend code belongs (controller, use case, service, action, repository, reader, mapper, resource), and whenever a review needs to cite a backend rule (review-branch loads it for processor-api/ diffs). Covers layer boundaries and dependency direction, Requests and Resources and the api.json/Orval contract, Result and TypedResults, repositories, readers and mappers, and the Docker test lane.
---

# Backend standards

Rules for code under `processor-api/`. Each rule is one file in `rules/`, named after its ID. They codify `processor-api/AGENTS.md` and `processor-api/.ai/guidelines/*`; those files keep direction, environment and process, and point here by ID. No rule here is a new architecture decision.

## How to use it

- **Writing code:** find the layer you are touching in the index and open those rule files. Read the nearest v1 module before inventing a shape (`ARCH-no-speculative-layers`).
- **Citing a rule in a review:** `<rule-id> (<IMPACT>) path:line`, for example `ARCH-thin-controllers (HIGH) processor-api/app/Http/v1/Articles/Controllers/ArticleController.php:88`. Quote the rule title, not a paraphrase.
- **Skip rules marked "Enforced by …"** in the rule file (and *(enforced by tooling)* below). A test, Pint, PHPStan or CI reports those; a reviewer does not.
- **Words** (controller, use case, port, adapter, mapper and so on) are defined in [references/concepts.md](references/concepts.md). How the predecessor skill was pressure-tested: [references/testing-record.md](references/testing-record.md).

## ID prefixes

| Prefix | Covers |
| --- | --- |
| `ARCH-` | Layer boundaries: what controllers, use cases and services may do, and which dependency directions are allowed |
| `CONTRACT-` | Requests, Resources, response annotations, the `api.json` and Orval workflow |
| `RESULT-` | `Result`, `TypedResults` and error mapping |
| `DATA-` | Repositories, `*Reader` interfaces, mappers, list-shape naming, SQL placement |
| `TEST-` | The Docker test lane, and which tests to add for what |

## Impact and review criticality

| Impact | What it means | Criticality of a breach (`review-branch`) |
| --- | --- | --- |
| CRITICAL | Breaks a layer boundary or the public contract for every caller | Warning by default; Critical when it causes wrong behaviour |
| HIGH | Real boundary, contract or correctness cost | Warning by default; Critical when it causes wrong behaviour |
| MEDIUM | Consistency with the v1 modules | Suggestion |
| LOW | Naming and local tidiness | Suggestion |

## Rule format

Frontmatter `title`, `impact` (CRITICAL, HIGH, MEDIUM or LOW), optional `impactDescription`, and `tags`. Then the rule, an **Incorrect** block (a shape the v1 migration removed, or legacy code still in the repo), a **Correct** block (from a v1 module), and the source line. A rule that tooling checks says **Enforced by …** under its heading.

## Rules by impact

### CRITICAL (1)

- [`ARCH-domain-dependency-direction`](rules/ARCH-domain-dependency-direction.md): Domain Never Imports Application, Infrastructure or Http *(enforced by tooling)*

### HIGH (13)

- [`ARCH-business-rules-in-application`](rules/ARCH-business-rules-in-application.md): Business Rules and Ownership Checks Live in the Application Layer
- [`ARCH-cross-module-service-interface`](rules/ARCH-cross-module-service-interface.md): Other Modules Call a *ServiceInterface That Returns Result
- [`ARCH-domain-no-framework`](rules/ARCH-domain-no-framework.md): No HTTP, Eloquent or Query Builders in the Domain
- [`ARCH-thin-controllers`](rules/ARCH-thin-controllers.md): Controllers Coordinate, They Do Not Orchestrate
- [`CONTRACT-api-json-orval-workflow`](rules/CONTRACT-api-json-orval-workflow.md): Contract Changes Flow Backend Source, then api.json, then Orval
- [`CONTRACT-named-resources`](rules/CONTRACT-named-resources.md): #[Response] References a Named Resource, Not an Inline Shape
- [`DATA-complete-domain-models`](rules/DATA-complete-domain-models.md): Domain Models Are Constructed Complete; Read Enrichment Lives Beside Them
- [`DATA-list-readers`](rules/DATA-list-readers.md): Filtered and Paginated List Reads Go Through a *ReaderInterface
- [`DATA-repositories-hide-persistence`](rules/DATA-repositories-hide-persistence.md): Repositories Hide Persistence and Return Domain Shapes
- [`RESULT-result-typed-errors`](rules/RESULT-result-typed-errors.md): Services Return Result With Typed Errors
- [`RESULT-typed-results`](rules/RESULT-typed-results.md): v1 Responses Are Resources or TypedResults, Never Ad Hoc JSON
- [`TEST-docker-lane`](rules/TEST-docker-lane.md): Run Backend Tests in the Isolated Docker Test Lane
- [`TEST-feature-tests-per-endpoint`](rules/TEST-feature-tests-per-endpoint.md): Endpoint Changes Get Feature Tests for Success, Failure and Edges

### MEDIUM (17)

- [`ARCH-focused-actions`](rules/ARCH-focused-actions.md): A Reusable or Side-Effectful Step Is a Focused Action
- [`ARCH-no-speculative-layers`](rules/ARCH-no-speculative-layers.md): Reuse the Nearest v1 Pattern Before Adding Layers
- [`ARCH-v1-routes`](rules/ARCH-v1-routes.md): New and Refactored Endpoints Go in routes/api_v1.php
- [`CONTRACT-comment-write-payload`](rules/CONTRACT-comment-write-payload.md): Comment Writes Use the Generic Enum Payload
- [`CONTRACT-enum-schema-components`](rules/CONTRACT-enum-schema-components.md): Public API Enums Are Backed Enums Shared by Request and Resource
- [`CONTRACT-fix-schema-at-resource`](rules/CONTRACT-fix-schema-at-resource.md): When a Component Is Wrong in api.json, Fix the Resource
- [`CONTRACT-partial-update-validation`](rules/CONTRACT-partial-update-validation.md): Partial Updates Validate Optional Fields and Reject an Empty Payload
- [`CONTRACT-request-per-endpoint`](rules/CONTRACT-request-per-endpoint.md): Each v1 Endpoint Validates Through Its Own Request Class
- [`CONTRACT-resource-scalar-casts`](rules/CONTRACT-resource-scalar-casts.md): Cast Public Scalars Explicitly in Resource toArray()
- [`CONTRACT-resources-shape-only`](rules/CONTRACT-resources-shape-only.md): Resources Shape the Response, They Do Not Decide
- [`CONTRACT-route-specificity`](rules/CONTRACT-route-specificity.md): Concrete Routes Before Parameterised Siblings, With Constraints
- [`DATA-list-shape-naming`](rules/DATA-list-shape-naming.md): List Shapes Use the Sibling Names
- [`DATA-mappers-translate-shape`](rules/DATA-mappers-translate-shape.md): Mappers Translate Shape and Nothing Else
- [`DATA-sql-in-infrastructure`](rules/DATA-sql-in-infrastructure.md): SQL Lives in Infrastructure; PostgreSQL Only
- [`RESULT-envelope-matches-module`](rules/RESULT-envelope-matches-module.md): Lists Use the Shared Envelope, Pagination and Include Flags
- [`RESULT-no-mixed-envelopes`](rules/RESULT-no-mixed-envelopes.md): One Endpoint Family, One Response Style
- [`TEST-openapi-schema-tests`](rules/TEST-openapi-schema-tests.md): Pin Non-Obvious Schema in an OpenApiTest

### LOW (3)

- [`CONTRACT-clean-array-param-names`](rules/CONTRACT-clean-array-param-names.md): Array Query Params Have Clean Rule Keys
- [`CONTRACT-no-key-comments-in-toarray`](rules/CONTRACT-no-key-comments-in-toarray.md): Implementation Notes Go Above the return, Not on Array Keys
- [`CONTRACT-v1-identifiers`](rules/CONTRACT-v1-identifiers.md): v1 Routes Address Entities by UUID Through EntityId
