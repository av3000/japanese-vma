# CI and Container Audit

> **Status:** Draft audit, 2026-09-05
> **Scope:** `.github/workflows/backend-ci.yml`, `.github/workflows/frontend-ci.yml`, `.gitlab-ci.yml`, `processor-api/docker-compose*.yml`, `processor-api/.docker/*`, `client/docker-compose.yml`, `client/.docker/*`
> **Trigger:** Review of GitHub Actions run 33974692949 (PR #167, job "Backend quality")

## 1. The CI run in question

The "Backend quality" job for PR #167 **succeeded** in about 1 minute 5 seconds. Step-level logs require authentication so per-step timings were not inspected. The only annotation is a runtime deprecation:

> Node.js 20 is deprecated. The following actions target Node.js 20 but are being forced to run on Node.js 24: actions/cache@v4.

Fix: bump `actions/cache@v4` to `actions/cache@v5` (Node 24 runtime, released 2025-12) in `backend-ci.yml:69`. `actions/checkout@v6` and `actions/setup-node@v6` are already on Node 24.

## 2. Findings, ranked

### High

| # | Where | Finding | Fix |
|---|---|---|---|
| H1 | `frontend-ci.yml:126,179,185` | `RENDER_DEPLOY_HOOK_URL` is a repository **variable**, not a secret, and is interpolated directly into `run:` scripts. GitHub prints the expanded script in the step log and does not mask `vars`. On a public repo the deploy hook URL is readable by anyone, and anyone with it can trigger production deploys. Render's docs say the URL is confidential. | Move it to `secrets`, expose via `env:`, and regenerate the hook in Render after the change. |
| H2 | `processor-api/.env.example:24-29`, `processor-api/docker-compose.yml:75-77,163,168` | A fresh `cp .env.example .env && docker compose up` cannot start. `DB_PASSWORD` is empty, so the `postgres` image refuses to initialise. `REVERB_PORT`, `REVERB_APP_ID/KEY/SECRET`, `REVERB_SCHEME` are absent from the example, so the `reverb` port mapping expands to `":"` and Compose rejects the file. `DB_USERNAME=root` is also a MySQL leftover. | Give `.env.example` compose-compatible defaults (`DB_USERNAME=japanese_vma`, non-empty `DB_PASSWORD`, `REVERB_PORT=8081`, placeholder Reverb app credentials) or add `${VAR:-default}` fallbacks in the compose file. |
| H3 | `backend-ci.yml`, `.gitlab-ci.yml` | Backend delivery is split across two systems. GitHub Actions runs tests on PRs to `develop` only; GitLab builds `Dockerfile.ci`, deploys the worker and triggers Render on the GitLab default branch. Consequences: the production Dockerfile is never built on a PR; release PRs into `master` get no backend check at all (`on.pull_request.branches: [develop]`); two sets of secrets and two dashboards to keep in sync. | Consolidate into GitHub Actions. GHCR is free for public repos and the frontend workflow already uses it. Port the GitLab jobs 1:1 (build+smoke, migrate manual, worker SSH deploy, Render hook, verify). Delete `.gitlab-ci.yml` once parity is confirmed. |

### Medium

| # | Where | Finding | Fix |
|---|---|---|---|
| M1 | `backend-ci.yml:84-102` | Larastan never runs in CI although `composer stan`, a baseline, and a `quality:ci` script exist. Pint only checks changed files, which is a fine ratchet, but `git diff` against `github.event.before` fails on the first push of a branch (all-zero SHA). | Add a `composer stan` step. Guard the diff: if `BASE_SHA` is all zeros, fall back to `git merge-base origin/develop HEAD`. |
| M2 | `frontend-ci.yml`, `client/vitest.workspace.ts` | `npm run test` is never executed in CI (53 test files exist). Likely cause: the workspace includes a Storybook browser project that needs Playwright Chromium. | Run `npx vitest run --project vite.config.ts` (unit only) in CI, or add `npx playwright install --with-deps chromium` and run everything. |
| M3 | `frontend-ci.yml:62-71,154-167` | The frontend is built three times per release: native `npm run build`, `docker build` in `verify`, and `build-push-action` in `publish`, with no layer cache between them. | Drop the native build (vite-plugin-checker already runs during the Docker build), use `docker/build-push-action` in `verify` with `push: false, load: true, cache-to: type=gha`, and reuse the cache in `publish`. Or merge `verify` and `publish` into one job with `push: ${{ is default branch }}`. |
| M4 | `frontend-ci.yml:167,185`, `.gitlab-ci.yml:358` | Deploys pull `:latest`. Rollback means rebuilding, and the deployed image cannot be traced to a commit from Render's side. | Render deploy hooks accept `?imgURL=ghcr.io/…:sha-<sha>` for image-backed services. Pass the SHA tag; keep `latest` only for humans. |
| M5 | `client/.docker/Dockerfile.production:10-19`, `frontend-ci.yml:160-164` | All runtime config (`VITE_API_URL`, Sentry, Reverb) is baked at build time, so one image equals one environment. `VITE_REVERB_*` is never passed in CI, so the production bundle has an empty Reverb key and websockets are silently disabled (`socket-provider.tsx:76,82`). | Short term: pass `VITE_REVERB_*` build args if realtime is meant to work in production. Longer term: inject a `window.__ENV__` script at container start via nginx `envsubst` so one image serves staging and production. |
| M6 | `client/.docker/Dockerfile.production:8`, `client/.gitignore` | `COPY . .` depends on `src/api/generated` existing in the build context, but that folder is gitignored and `api.json` lives outside the `client/` context. A plain `docker build` from a fresh clone fails; the image only builds because CI runs Orval first. | Either build with `context: .` (repo root) and run `npm run orval:file` inside the Dockerfile, or commit the generated client. |
| M7 | `processor-api/.docker/Dockerfile` | Dev image: `COPY . .` precedes `composer install`, so every source change re-runs Composer; `build-essential`, `nodejs`, `npm` are installed only to serve a dead Laravel Mix setup; no `--no-install-recommends` or apt cache cleanup; image runs as root. | Copy `composer.json`/`composer.lock` first and run `composer install --no-scripts --no-autoloader`, then copy source and `composer dump-autoload`. Drop Node. Add `USER www-data` or `--user` in compose. |
| M8 | `processor-api/.docker/Dockerfile.ci` | Production image: no `config:cache` / `route:cache` / `view:cache`, no opcache tuning, no `HEALTHCHECK`, build tools (`g++`, `make`, `autoconf`, `*-dev`) remain in the final layer, `pdo_sqlite` is kept only for the GitLab smoke test. | Multi-stage: build extensions in one stage, copy `/usr/local/lib/php/extensions` and `/usr/local/etc/php` into a slim runtime. Run the smoke test against a Postgres service and drop SQLite, matching PR #163. Add `HEALTHCHECK CMD curl -f http://localhost/api/health`. |
| M9 | `processor-api/docker-compose.yml` | Drift and coupling: `queue` runs `queue:work` while production runs Horizon; `laravel-app` waits on `reverb` for no reason; `redis:7.0.2` (2022) and `redisinsight:1.12.0` are stale pins; `db-test`/`test-runner` are duplicated with `docker-compose.test.yml`; `laravel-app` health is inferred from `/proc/net/tcp6`; Postgres and Redis publish on `0.0.0.0`. | Run `php artisan horizon` locally too. Remove the `reverb` dependency. Pin `redis:7.4-alpine` and a current RedisInsight 2.x. Put the test lane behind a `profiles: [test]` block or keep only `docker-compose.test.yml`. Health-check with `php-fpm-healthcheck` or `pidof php-fpm`. Bind host ports to `127.0.0.1:`. |
| M10 | `client/.docker/nginx.conf.template` | No gzip/brotli, no `Cache-Control` split (hashed assets immutable, `index.html` no-cache), no security headers, no healthcheck. | Add `gzip on`, `location ~* \.(js|css|woff2)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }`, `location = /index.html { add_header Cache-Control "no-cache"; }`, and a `HEALTHCHECK` in the Dockerfile. |

### Low

| # | Where | Finding | Fix |
|---|---|---|---|
| L1 | `.gitlab-ci.yml:105` | `${TELESCOPE_ENABLED:false}` is substring expansion, not a default. It writes `TELESCOPE_ENABLED=` (empty). Harmless today because empty is falsy, but it is a typo. | `${TELESCOPE_ENABLED:-false}` |
| L2 | `package-lock.json` (repo root) | Stray lockfile named after an old worktree with an empty `packages` map. | Delete it. |
| L3 | `processor-api/package.json`, `webpack.mix.js`, `resources/js`, `resources/sass` | Legacy Laravel Mix / React 16 tooling that nothing uses; it is why the dev Dockerfile installs Node. | Remove. |
| L4 | `AGENTS.md:19`, `processor-api/.github/copilot-instructions.md` | `docs/frontend-github-actions-plan.md` does not exist; Copilot instructions say Laravel 11 while `composer.json` requires `^12`. | Update or delete the references. |
| L5 | both workflows | No `concurrency` group, so rapid pushes to one PR run duplicate jobs. | `concurrency: { group: ${{ github.workflow }}-${{ github.ref }}, cancel-in-progress: true }` |
| L6 | `frontend-ci.yml:39,59,71` | `${{ vars.X }}` is spliced directly into shell. Safe for these values, but the habit is what leaked H1. | Map every expression through `env:` and reference `$VAR` in the script. |
| L7 | `backend-ci.yml:3-7` | No `paths` filter, so frontend-only PRs run the full backend suite. | Mirror the frontend filter with `processor-api/**` and the workflow file. |

## 3. Hosting plan: zero-cost first, scalable later

Constraints observed in the repo: Laravel API with Passport, Horizon worker, Reverb websockets, PostgreSQL 17, Redis (Upstash), a static Vite frontend, existing GCP VM for the worker, Render for the web service.

### Free-tier reality check (verified 2026-09-05)

| Service | Free tier | Fit |
|---|---|---|
| Render web service | 750 h/month, spins down after 15 min idle, ~1 min cold start, ephemeral disk | OK for a demo API; cold starts hurt a chatty SPA |
| Render Postgres | **Expires 30 days after creation**, 1 GB | Not usable for anything persistent |
| Render Key Value | 1 instance, in-memory only | Unsuitable for Horizon queues |
| Railway | $1/month credit on Free, $5 one-time trial, Hobby is $5/month | Effectively not free anymore |
| Neon Postgres | Free project, ~0.5 GB, scale-to-zero, pooled connection string | Good fit; `DB_SSLMODE=require` already supported |
| Supabase Postgres | 500 MB, pauses after 7 days of inactivity | Works, pausing is annoying |
| Upstash Redis | Already in use, free tier with TLS | Keep |
| GHCR | Free for public repos | Keep, already used by frontend |
| Cloudflare Pages / GitHub Pages | Free static hosting, no spin-down, global CDN | Best home for `client/build` |
| Oracle Cloud Always Free | 4 ARM OCPU / 24 GB RAM VM, permanent | Best place to run the whole backend compose stack for $0 |
| GCP e2-micro | 1 shared vCPU / 1 GB, always free in some US regions | Enough for Horizon worker, tight for web+worker+reverb |

### Recommended topology

**Phase 1, local.** Fix H2 and M9 so `docker compose up` works from a fresh clone on both apps. Keep the `docker-compose.test.yml` lane as the single test path.

**Phase 2, CI.** One GitHub Actions workflow per app, each doing lint/typecheck/tests on PRs, then build → smoke test → push to GHCR with the SHA tag on the default branch. Backend adds a manual `migrate` job and the worker SSH deploy, ported from GitLab. Cache with `type=gha`.

**Phase 3, deploy at $0.**
- Frontend: `client/build` to Cloudflare Pages (or GitHub Pages). Keep the nginx image as the self-host/parity artifact.
- Backend web + Horizon + Reverb + local Redis: one VM (the existing GCP VM, or an Oracle Always Free ARM VM if you want headroom) running `docker compose` with Caddy in front for automatic TLS. The existing `deploy_worker` SSH job already does 90% of this; extend `.deploy/worker/docker-compose.yml` into `.deploy/vm/docker-compose.yml` with `web`, `worker`, `reverb`, `caddy`. This removes the Render cold start and the cross-provider ordering problem described in `deployment-and-runtime.md`.
- PostgreSQL: Neon free project. Use the pooled URL for the web service and the direct URL for migrations.
- Redis: keep Upstash if you want managed durability, or run Redis in the compose stack once everything is on one VM.
- Websockets: Reverb behind Caddy on the same VM. If the VM route is rejected, the client already has a Pusher broadcaster config; Pusher's free tier (100 connections, 200k messages/day) is the fallback.

**Scaling path.** Each piece above has a paid successor without an architecture change: Render/Fly for the web container, Neon paid for Postgres, Upstash paid for Redis, a second VM or a managed container service for workers. Because everything is a GHCR image plus env vars, moving a service is a compose or dashboard change, not a code change.

## 4. Suggested order of work

1. H1 (secret leak) and the `actions/cache@v5` bump. Ten minutes, no risk.
2. H2 and M9: make local compose work from a fresh clone.
3. M1, M2, L5, L7: make PR CI actually cover stan and frontend tests.
4. M3, M4: cache the Docker builds, deploy by SHA.
5. H3: port the GitLab pipeline into GitHub Actions and retire it.
6. M7, M8, M10: harden the images.
7. Phase 3 hosting move.
