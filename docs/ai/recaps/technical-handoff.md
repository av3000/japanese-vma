# Technical Handoff

> **Status:** Baseline implementation entry point
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Repository working tree inspected on 2026-08-18
> **Audience:** Engineers and AI-assisted contributors starting repository work

## Read Before Editing

1. `AGENTS.md`
2. `CONTEXT.md`
3. `client/AGENTS.md` or `processor-api/AGENTS.md`
4. [Current and Target State](../current-target-state.md)
5. The owning [feature packet](../../feature-artifacts/)
6. [Evidence Manifest](../evidence-manifest.md) when a claim affects contracts, architecture, or deployment

## Repository Map

| Path | Responsibility |
|---|---|
| `client/` | React/Vite application, generated clients, feature query modules, routes, and UI tests. |
| `processor-api/` | Laravel API, domain/application/infrastructure modules, queues, realtime, PDFs, imports, and backend tests. |
| `docs/` | Shared architecture, feature, migration, agent, and validation knowledge. |
| `.github/workflows/frontend-ci.yml` | Frontend verification/deployment workflow. |
| `.gitlab-ci.yml` | Backend image and cross-system deployment orchestration. |

## Preferred Flow Maps

Frontend:

```text
route -> feature component -> feature API/query hook -> generated client
```

Backend v1:

```text
route -> controller -> request -> DTO/value object -> application service/action
      -> repository interface -> infrastructure adapter -> resource -> TypedResults
```

Use existing article and catalogue modules as precedents. Do not copy class components, raw endpoint strings, page-owned server pagination, or Eloquent-shaped transport from legacy code.

## Generated Contract Workflow

Run backend generation and frontend generation sequentially:

```powershell
cd processor-api
composer openapi

cd ../client
npm run orval:file
```

Before trusting generated output:

1. inspect the backend Request/Resource/enum/response annotation;
2. inspect the affected schema in `processor-api/api.json`;
3. regenerate Orval;
4. inspect the generated model/client;
5. run focused backend schema/feature tests and frontend type/tests.

Never hand-edit `client/src/api/generated/`.

## Verification Lanes

### Frontend

From `client/`:

```powershell
npm run typecheck
npm run test -- <focused-test>
npm run build
```

Sandboxed Vite/Vitest can fail at startup with an environment-level `spawn EPERM`. Re-run the same command outside the sandbox before classifying that as a product failure.

### Backend

From `processor-api/`:

```powershell
docker compose up -d --build db-test test-runner
docker compose exec test-runner composer test -- tests/Feature/<FocusedTest>.php
```

Do not run DB-backed backend tests against host PHP, the main development database, the application container, or an SQLite fallback.

### Documentation

From the repository root:

```powershell
pwsh -NoProfile -File docs/scripts/validate-docs.tests.ps1
pwsh -NoProfile -File docs/scripts/validate-docs.ps1
git diff --check
```

## Deployment Boundaries

- Frontend: GitHub Actions.
- Backend image/orchestration: GitLab CI.
- Backend web runtime: Render.
- Queue worker: GCP VM via Docker Compose.
- Production Redis coordination: Upstash.

Queue, environment, serialization, cache, or worker changes are cross-system changes. Review application configuration, Compose/runtime, CI/CD, provider rollout, and docs together.

## Common Traps

- Treating a registered v1 route as complete without controller/test evidence.
- Running Orval from stale `processor-api/api.json`.
- Adding frontend coercion for a backend schema-generation bug.
- Adding a custom wrapper that only renames a usable generated client.
- Extending legacy SavedList, community, or Japanese detail patterns during migration.
- Mixing publicity, moderation status, and processing status.
- Assuming provider health from repository configuration.
- Modifying unrelated dirty-worktree files.

## Task-Start Checklist

- Identify the owning feature and current/target state.
- Map the complete current flow before proposing changes.
- Search active callers and generated clients.
- Decide whether the issue is contract-first or caller-only.
- Preserve legacy behavior unless a change is explicit.
- Select focused tests before editing.
- Record runtime or provider checks that remain unverified.
- Keep the diff scoped and report exact commands/outcomes.
