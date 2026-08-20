# Deployment and Runtime

> **Status:** Baseline; repository configuration verified, provider state unverified
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Engineers, operators, incident responders, and reviewers

## Runtime Topology

The production topology is intentionally cross-system:

| Concern | Configured owner |
|---|---|
| Frontend verification and deploy | GitHub Actions |
| Backend image build and orchestration | GitLab CI |
| Backend web service | Render |
| Queue worker | Docker Compose on a GCP VM |
| Shared Redis queue/cache coordination | Upstash Redis |
| Primary application persistence | MySQL-compatible database configuration |

This review verified repository configuration and contributor guidance. It did not inspect provider dashboards or make live requests.

## Deployment Flow

```mermaid
sequenceDiagram
    participant Git as Source repository
    participant GHA as GitHub Actions
    participant GL as GitLab CI
    participant Registry as Container registry
    participant GCP as GCP worker VM
    participant Render as Render web service
    participant Redis as Upstash Redis

    Git->>GHA: frontend workflow trigger
    GHA->>GHA: install, verify, build
    GHA-->>GHA: publish frontend artifact/site

    Git->>GL: backend pipeline trigger
    GL->>Registry: build and publish backend image
    GL->>GCP: deploy worker over SSH
    GCP->>Redis: start worker against shared queue
    GL->>GCP: verify worker
    GL->>Render: trigger backend web deploy
    Render->>Redis: use shared cache/queue configuration
```

## Deployment Ordering

The configured backend sequence deploys and verifies the worker before triggering the Render web deployment. That ordering reduces the chance that newly deployed web code enqueues jobs that no compatible worker can consume.

Any production change involving jobs, serialization, queue names, Redis behavior, or environment variables must be reviewed across:

- application configuration;
- the worker Compose/runtime definition;
- GitLab CI deployment steps;
- Render web configuration;
- operational documentation.

## Local Runtime

Backend Compose commands run from `processor-api/`; frontend Compose commands run from `client/`. There is no assumed repository-root Compose file.

The backend Compose topology provides the application/web services plus dedicated database and test-runner services. Backend database tests must use the isolated `db-test` and `test-runner` lane rather than host PHP, the development database, or SQLite substitutes.

## Verification Boundaries

Repository evidence supports these checks:

- frontend typecheck, tests, and build through commands in `client/package.json`;
- backend health and route inspection through the Laravel container;
- backend feature tests through the dedicated Docker test lane;
- worker verification through the GitLab pipeline and backend operational command tests.

Provider-level verification remains an operational action. A documentation review cannot confirm:

- the latest workflow or pipeline succeeded;
- the Render service is serving the expected image;
- the GCP worker is running the expected revision;
- Upstash connectivity or queue depth is healthy;
- rollback credentials and provider retention settings are valid.

## Rollback and Compatibility Risks

- Web and worker releases can disagree on queued payloads if serialization contracts change incompatibly.
- Removing legacy endpoints before callers are migrated creates immediate frontend failures.
- Publishing generated clients from stale OpenAPI can compile while misrepresenting runtime payloads.
- Redis/cache shape changes need a deliberate compatibility and invalidation plan.
- Database migrations must remain compatible with the deploy order and both running revisions during rollout.

## Operational Sources

- `AGENTS.md`
- `.github/workflows/frontend-ci.yml`
- `.gitlab-ci.yml`
- `processor-api/docker-compose.yml`
- `client/docker-compose.yml`
- `processor-api/tests/Feature/OperationalRoutesTest.php`
- `processor-api/tests/Feature/Console/VerifyQueueWorkerCommandTest.php`
