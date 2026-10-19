---
title: Run Backend Tests in the Isolated Docker Test Lane
impact: HIGH
impactDescription: tests hit their own PostgreSQL, never another checkout or the dev database
tags: test, docker, phpunit, postgresql
---

## Run Backend Tests in the Isolated Docker Test Lane

**Impact: HIGH (tests hit their own PostgreSQL, never another checkout or the dev database)**

Run backend tests in a lane started from `docker-compose.test.yml` with a project name unique to the checkout. Laravel then boots with `.env.testing` (`APP_ENV=testing`, `DB_HOST=db-test`, PostgreSQL 17 on tmpfs). Never use `php artisan test`, host PHP, the main dev database, SQLite, or the default `docker-compose.yml` from a worktree (its fixed container names can attach to another checkout). Run the tests the change touches, not the whole suite.

**Incorrect:**

```bash
php artisan test --compact tests/Feature/Catalogues
docker compose exec test-runner composer test     # default compose file, from a worktree
```

**Correct (from `processor-api/`):**

```bash
docker compose -f docker-compose.test.yml -p jvma502 up -d --build
docker compose -f docker-compose.test.yml -p jvma502 exec -T test-runner php artisan passport:keys --force
docker compose -f docker-compose.test.yml -p jvma502 exec -T test-runner composer test -- tests/Feature/Catalogues
```

Source: `.ai/guidelines/enforce-tests.blade.php`, `.ai/guidelines/phpunit/core.blade.php`, `processor-api/AGENTS.md` §6.
