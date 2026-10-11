# Local environment: worktrees, lanes and known traps

Most work here happens in git worktrees under `.worktrees/`, often several at once, on a Windows host with Docker Desktop. Each entry below cost someone a diagnostic detour, because the failure looked like a product bug. Read it before concluding anything from a red check in a fresh worktree.

**Keeping this current:** when a script or check makes a trap impossible, delete its entry. A trap that is easy to hit again gets a mechanical guard, not a longer paragraph here.

## Starting a worktree

- **Branch from `develop`, never `master`.** `master` only receives release merges and lags by dozens of PRs, so code read there may no longer exist.
  ```bash
  git worktree add -b <branch> .worktrees/<name> origin/develop
  git -C .worktrees/<name> log --oneline -1   # must match the develop tip
  ```
- **Push with an explicit refspec.** A branch created from `origin/develop` tracks it, so a bare `git push` targets `develop`:
  ```bash
  git push -u origin <branch>:<branch>
  ```
- **Check the branch before every commit and push.** `git branch --show-current` must be the branch of the task you are on. If the working directory changed mid-session, that is not a new instruction: "commit if not yet" means the work under discussion, not whatever is dirty in front of you.

## Backend: one isolated test lane per worktree

`processor-api/AGENTS.md` ("Testing & Verification Expectations") has the commands. The traps behind them:

| Symptom | Cause | Do this |
| --- | --- | --- |
| Tests or `composer openapi` change files in another checkout | The default `docker-compose.yml` pins container names (`laravel_test_runner`, `laravel_app`, …), and names are global to the Docker daemon | Use `-f docker-compose.test.yml -p <unique>`. Check which tree a container mounts: `docker inspect <container> --format '{{index .Config.Labels "com.docker.compose.project.working_dir"}}'` |
| Every `auth:api` test returns 500, `LogicException: Invalid key supplied` | A fresh lane has no Passport keypair (`storage/oauth-*.key` is gitignored) | `... exec -T test-runner php artisan passport:keys --force` |
| Data imported for a benchmark is gone after a test run | `RefreshDatabase` wipes the lane database | Re-import after each suite run, before measuring |
| `fork/exec /usr/bin/runc: resource temporarily unavailable` when starting a lane | Idle test-runners from other lanes hold 3,000–4,000 processes each | `docker stats --no-stream --format '{{.Name}} {{.PIDs}}'`. Stop lanes of merged work with `docker compose -p <name> stop`. Ask before stopping someone else's lane, and never `down -v` it |
| `format-changed` refuses to run | From a worktree, `laravel-app` belongs to another checkout | `LANE=<name> ./format-changed.sh` or `./format-changed.ps1 -Lane <name>` |

- **The test lane needs no `processor-api/.env`.** `docker-compose.test.yml` reads the tracked `.env.testing`. Only the default compose file interpolates `.env`, and that file holds secrets, so don't copy it around.
- **Run the tests the change touches,** not the full suite: the changed module's test folders, plus anything in `tests/` that references the changed classes or routes. Say in the report which areas ran.
- **PHPStan over the bind mount is slow** (past six minutes for a handful of files). Run it in the background with a timeout, not in the foreground.

## Frontend: a fresh worktree

- **`client/node_modules` and `client/src/api/generated/` are absent.** Run `npm ci`, then `npm run orval:file`. Without the generated client, every generated import fails in a way that looks like a regression.
- **A `node_modules` junction to the main checkout** (only when `package-lock.json` is identical) is enough for ESLint, `tsc`, the unit Vitest project, Orval and `vite`. The Storybook browser project fails on it ("outside of Vite serving allow list"); let CI run that one.
- **Remove a junction before removing the worktree:** `cmd /c rmdir client\node_modules`, then `git worktree remove`. Otherwise deletion can follow the junction into the main checkout.
- **`client/.env`:**
  - `VITE_API_URL` must be set for `npx vite` and for `src/services/orval-mutator.test.ts`. Without it that test fails with `undefined/api/v1/`, which is an environment artefact.
  - `ORVAL_OPENAPI_URL` in `.env` overrides `orval:file`. While a local backend is up, Orval then reads the *running* backend's schema, which can be older than the committed `processor-api/api.json`. To force the committed file from Git Bash: `ORVAL_OPENAPI_URL="$(cd ../processor-api && pwd -W)/api.json" npm run orval:file`.
- **Formatting:** `npm run format -- <paths>`. There is deliberately no whole-repo format script: the repo is not uniformly formatted, and one full run dirtied 138 unrelated files.

## Sandboxes and the Windows host

- **`spawn EPERM` from esbuild** when Vitest or Vite starts inside an agent sandbox: rerun the same command outside the sandbox before treating it as a failure.
- **A Vitest run that exits 0 after one file:** inside the sandbox, the Storybook browser project dies with "Browser connection was closed" and still exits 0. Read the `Test Files N passed (M)` line, not the exit code. If N < M, rerun outside the sandbox.
- **Slow containers: suspect the bind mount first.** Each file operation on the Windows bind mount costs about a millisecond, so code doing many small reads is slow locally and instant in production. Copy the input to container-local `/tmp` and re-time it. If it collapses, move that path to an anonymous volume, the way `processor-api/docker-compose.yml` treats `vendor/`. Caches and generated files never belong on the mount.
- **Playwright MCP** writes `.playwright-mcp/` logs into the main checkout root, even when you work in a worktree. The folder is gitignored; delete it after a session.

## Git and GitHub

- **`git branch --merged` lies here:** PRs are squash-merged, so merged branches are reported as unmerged. Judge merge state from the PR: `gh pr list --state all --json headRefName,state`.
- **Edit issue and PR bodies with a file:** `gh issue view N --json body --jq .body > body.md`, edit it, diff it, then `gh issue edit N --body-file body.md`. PowerShell captures `gh` output as an array of lines and can upload a body with all newlines lost. A damaged body can be restored from the GraphQL `userContentEdits` history.
