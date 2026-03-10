# Simplified First Rollout Deliverables

- [x] A) Concise architecture explanation
- [x] B) Updated `.gitlab-ci.yml` complete file
- [x] C) Laravel health endpoint changes
- [x] D) Safe migration job and deploy ordering
- [x] E) Manual one-time Japanese import job and rerun guard
- [x] F) Test plan for `/`, `/api/health`, `/api/v1/articles?per_page=1`, and post-import Japanese data checks

## Architecture

- GitLab is the single backend release orchestrator. It builds and smoke-tests the backend image, runs `php artisan migrate --force` from that exact image, triggers the Render deploy hook, and then verifies the live service over HTTP.
- The deployed runtime stays minimal for free tiers: one Render web service pulling `latest` from GitLab Container Registry, one Supabase Postgres database with SSL required, and Laravel configured with `QUEUE_CONNECTION=sync`.
- Local `docker-compose` remains a development stack, not a production mirror. It still includes MySQL, Redis, Reverb, and worker services that are intentionally not part of the first production rollout.
- Japanese data import stays outside the normal deploy path. The manual `php artisan app:import-japanese-data` command records completion in `environment_bootstrap_runs` so each environment imports once unless rerun is explicitly requested.

## Rollout Flow

- `build_backend_image` builds the Docker image, smoke-tests `GET /` and `GET /api/health`, pushes the commit SHA image, and updates `latest` on the default branch.
- `migrate_backend` is manual on the default branch for the first rollout. It runs migrations against Supabase using the exact image built in CI.
- `deploy_render` is manual on the default branch for the first rollout. It calls the protected Render Deploy Hook only after migrations succeed.
- `verify_render` runs after deploy and checks `GET /`, `GET /api/health`, and `GET /api/v1/articles?per_page=1`.
- `import_japanese_data` stays manual and is not part of the normal deploy sequence.

## Required Infrastructure

- GitLab protected and masked variables:
  - `APP_KEY`
  - `APP_URL`
  - `DB_CONNECTION=pgsql`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
  - `DB_SSLMODE=require`
  - `RENDER_DEPLOY_HOOK_URL`
  - `RENDER_BASE_URL`
- Render runtime environment variables:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_KEY`
  - `APP_URL`
  - `DB_CONNECTION=pgsql`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
  - `DB_SSLMODE=require`
  - `QUEUE_CONNECTION=sync`
  - `CACHE_DRIVER=file`
  - `SESSION_DRIVER=file`
- Render service setup:
  - one web service using the GitLab Container Registry image
  - image tag set to `latest`
  - registry credentials configured in Render
- Database setup:
  - one Supabase project
  - direct connection details available to both Render and GitLab CI

## Validation

- Local CI image build:
  - `docker build --pull -f processor-api/.docker/Dockerfile.ci -t processor-api-ci-test processor-api`
- Route test in Docker:
  - `docker run --rm -v "${PWD}\\processor-api:/var/www/html" -w /var/www/html -e APP_ENV=testing -e APP_DEBUG=false -e APP_KEY=<throwaway test key> -e APP_URL=http://localhost -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_DRIVER=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array -e TELESCOPE_ENABLED=false processor-api-ci-test ./vendor/bin/phpunit --filter=OperationalRoutesTest`
- Japanese import unit tests in Docker:
  - `docker run --rm -v "${PWD}\\processor-api:/var/www/html" -w /var/www/html -e APP_ENV=testing -e APP_DEBUG=false -e APP_KEY=<throwaway test key> -e APP_URL=http://localhost -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory: -e CACHE_DRIVER=array -e SESSION_DRIVER=array -e QUEUE_CONNECTION=sync -e MAIL_MAILER=array -e TELESCOPE_ENABLED=false processor-api-ci-test ./vendor/bin/phpunit tests/Unit/JapaneseDataImport`

## Test Plan

- Pre-release CI checks:
  - `GET /` returns HTML containing `id="root"`
  - `GET /api/health` returns `200` with exact JSON `{"ok":true}`
- First live rollout checks:
  - `GET /` returns the SPA shell
  - `GET /api/health` returns `200` with exact JSON `{"ok":true}`
  - `GET /api/v1/articles?per_page=1` returns `200` and the expected success envelope
- Post-import checks:
  - run `import_japanese_data` manually once
  - verify a Japanese-data route such as `GET /api/v1/kanjis?per_page=1`
- Import safety checks:
  - repeated manual runs no-op when the sentinel exists
  - reruns happen only when `IMPORT_FORCE_RERUN=true`
