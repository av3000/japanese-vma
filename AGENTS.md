# AGENTS.md

This file provides **repository-wide** guidance for AI agents and contributors working in `japanese-vma`.

## 1) Repository Purpose & Shape

- **Product goal:** Japanese learning platform with content, community, and Japanese language study resources.
- **Main applications:**
  - `processor-api/` → Laravel API.
  - `client/` → React application.
  - `docs/` → documentation assets.
- **Scoped instructions:**
  - Use this root file for cross-cutting repo rules.
  - Use `processor-api/AGENTS.md` for backend-specific implementation rules.
  - Use `client/AGENTS.md` for frontend-specific implementation rules.

### Current Live Environment

- **Frontend live hosting:** GitHub Actions workflow deploys the frontend; see `docs/frontend-github-actions-plan.md` for pipeline context.
- **Backend live hosting:** Render hosts the live Laravel API/web service.
- **Queue / worker runtime:** Production queue workers run on a GCP VM via Docker Compose, not on Render.
- **Queue backend:** Upstash Redis is used for live Redis-backed queue/cache coordination.
- **Backend deployment flow:** GitLab CI builds the backend image, deploys the worker to the GCP VM over SSH, verifies the worker, then triggers the Render backend deploy.
- **Rule for agents:** Treat live environment changes as cross-system work. Check backend config, worker compose/runtime, CI/CD, and docs together before proposing or implementing production changes.

## 2) Developer Experience (DX) Baseline

- **Recommended local tooling:**
  - Git + conventional commit hygiene.
  - Node/npm for frontend workflows.
  - PHP/Composer for Laravel workflows.
  - Docker/docker-compose for full-stack local environment when needed.
  - Docker Compose files are app-scoped in this repo:
    - run backend compose commands from `processor-api/`
    - run frontend compose commands from `client/`
    - do not assume a repo-root compose file exists
- **Editor quality-of-life plugins (recommended):**
  - ESLint + Prettier extensions for frontend lint/format feedback.
  - EditorConfig support.
  - PHP Intelephense (or equivalent) for Laravel navigation.
  - Tailwind CSS IntelliSense for utility-class workflows.
- **Before opening PRs:**
  - Run targeted checks for touched surface area first.
  - Run broader checks when practical.
  - Keep outputs and assumptions explicit in summaries.
- **Generated API contract workflow:**
  - When TypeScript API models or generated clients drift from backend expectations, fix the backend schema source first.
  - Preferred order: backend Request/Resource/response annotation update → `composer openapi` → `npm run orval:file`.
  - Run `composer openapi` and `npm run orval:file` sequentially, not in parallel, or Orval may regenerate from a stale `processor-api/api.json`.
  - If Orval output looks wrong, inspect `processor-api/api.json` before adding frontend adapters or type coercion.
  - Prefer fixing schema generation at the backend source even if the runtime endpoint itself appears correct.
  - Verify the regenerated schema before trusting regenerated client types.
  - Do not hand-edit generated API files to patch over contract problems.
  - When a v1 endpoint is already documented and Orval already generates a usable client, prefer that generated client over adding a custom frontend wrapper. Only add a custom adapter when the endpoint is legacy, missing from the schema, or the generated client is actually unusable.

## 3) How to Work in This Repository

- **Change strategy:**
  - Keep diffs focused; avoid unrelated refactors.
  - Prefer incremental, reviewable changes.
  - Reuse existing local patterns before introducing new abstractions.
- **Architecture strategy:**
  - Maintain separation of concerns and avoid cross-layer leakage.
  - Prefer consistency with neighboring code over inventing parallel styles.
- **Migration strategy:**
  - Legacy and v1 paths may coexist; migrate intentionally and incrementally.
  - Preserve behavior unless change is intentional and documented.

## 4) Cross-Cutting Quality Standards

- **Clarity:**
  - State assumptions and constraints explicitly.
- **Traceability:**
  - Include file-level summaries and commands used for validation.
- **Reliability:**
  - Validate changed behavior with tests/checks where feasible.
  - If environment blocks a check, report the limitation clearly.
  - For backend verification, use the dedicated Docker test lane from `processor-api/`: `docker compose up -d --build db-test test-runner`, then `docker compose exec test-runner composer test -- ...`.
  - Do not run DB-backed backend tests against host PHP, `laravel-app`, the main dev database, or SQLite fallbacks.
  - In the current sandboxed frontend setup, Vitest/Vite may fail at startup with `spawn EPERM` from `esbuild`; rerun the same test command outside the sandbox before treating it as an application failure.
  - Treat that as an environment limitation to report clearly, not as a product bug to “fix” inside unrelated feature work.
- **Safety:**
  - Do not silently alter contracts or conventions.
  - Highlight behavior-impacting changes and rollout implications.

## 5) Prompting Guidance (All Domains)

- **For planning tasks:**
  - Include scope, non-goals, required format, and acceptance criteria.
  - Request discovery summary before proposed implementation steps.
- **For implementation tasks:**
  - Specify target modules/files, constraints, and validation expectations.
  - Ask for “must-do now” vs “follow-up” recommendations when refactoring.

## 6) Domain-Specific Instruction Files

- Backend rules live in: `processor-api/AGENTS.md`
- Frontend rules live in: `client/AGENTS.md`
- Live backend deployment and worker behavior may involve Render, GitLab CI, GCP VM worker runtime, and Upstash Redis together; keep root deployment context in mind when editing backend infrastructure or queue-related code.

When touching files under either subtree, treat the scoped AGENTS file there as the primary implementation guide.

## 7) Laravel AI Guidance

- Repository-level Laravel AI guidance lives in `.ai/guidelines/` and `.ai/skills/`.
- For legacy Laravel endpoint migrations into the v1 architecture, prefer `.ai/skills/legacy-to-v1-migration/`.
- Treat `.ai/` guidance as the canonical AI workflow layer for this repository when Laravel Boost or another compatible agent setup is available.
- Repo and scoped `AGENTS.md` constraints still take precedence over generic clean architecture advice.
