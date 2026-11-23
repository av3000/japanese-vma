# Client npm-to-pnpm Migration Specification

**Status:** Approved design

**Date:** 2026-08-24

**Scope:** `client/` package context and the automation and documentation that operate it

## Summary

Japanese VMA will replace npm with pnpm for the React client in one focused pull request. The migration is container-first: normal contributors continue to need Docker, Docker Compose, Git, and the existing environment files on the host, but they do not need Node, Corepack, or pnpm installed locally.

The migration pins stable pnpm `11.23.0`, replaces `client/package-lock.json` with `client/pnpm-lock.yaml`, keeps pnpm's strict dependency layout, reviews dependency build scripts explicitly, and updates development Docker, production Docker, GitHub Actions, scripts, and maintained contributor guidance together. It does not create a repository-level JavaScript workspace or migrate the legacy Laravel Mix package in `processor-api/`.

## Decisions

| Area | Decision |
| --- | --- |
| Migration boundary | Migrate only the React package rooted at `client/`. |
| Delivery | Complete the switch atomically in one pull request. |
| Package manager | Pin the exact stable version `pnpm@11.23.0`. |
| Host workflow | Keep normal development container-only; pnpm runs inside client containers. |
| Dependency graph | Import the existing npm resolution and do not bundle dependency upgrades. |
| Dependency strictness | Keep pnpm's standard isolated layout; do not enable `shamefullyHoist`. |
| Install scripts | Explicitly allow or deny dependency build scripts; do not allow all scripts globally. |
| CI | Migrate GitHub Actions in the same pull request and cache the pnpm store. |
| Workspace behavior | Use `client/pnpm-workspace.yaml` only for pnpm project settings; do not add other packages. |

## Goals

- Make pnpm the only supported package manager for `client/`.
- Make local Docker, production Docker, and GitHub Actions resolve the same lockfile with the same pnpm version.
- Preserve application behavior and the existing direct dependency versions.
- Gain pnpm's strict dependency visibility, content-addressable store, deterministic frozen installs, and explicit dependency build-script policy.
- Give container-first contributors a short, accurate command reference and a safe transition procedure for existing Docker volumes.
- Leave the repository in a state where accidentally running npm cannot produce a second tracked client lockfile.

## Non-goals

- Migrating `processor-api/package.json` or `processor-api/package-lock.json`.
- Removing or interpreting the root `package-lock.json`, which has no matching root `package.json`.
- Creating a root `pnpm-workspace.yaml` or turning the repository into a pnpm monorepo.
- Upgrading React, Vite, Storybook, Orval, test tools, or other application dependencies.
- Refactoring application code, generated Orval code, Docker service topology, or the nginx runtime image.
- Requiring or standardizing native Windows, macOS, or Linux Node development.
- Adding advanced Docker store mounts, `pnpm fetch`, or offline-install optimization before the basic migration has demonstrated a need for them.

## Current State

The client currently has the following npm assumptions:

- `client/package-lock.json` is the authoritative dependency lockfile.
- `client/.docker/Dockerfile` installs with `npm install` and starts the app with npm.
- `client/.docker/Dockerfile.production` installs with `npm ci` and builds with npm.
- `client/docker-compose.yml` starts Vite and Storybook with npm commands.
- `.github/workflows/frontend-ci.yml` configures npm caching, installs with `npm ci`, and runs client scripts with npm in both the verification and publishing jobs.
- `client/package.json` contains npm-specific script chaining and an `npx chromatic` invocation.
- Root and client contributor documentation describes npm as the frontend workflow.

There are three tracked npm lockfiles in the repository, but this design intentionally changes only `client/package-lock.json`. The Laravel package and root lockfile are separate concerns.

## Target State

### Package-manager ownership

`client/package.json` is the declaration of package-manager ownership. It must contain:

```json
{
  "packageManager": "pnpm@11.23.0",
  "engines": {
    "node": ">=24 <25",
    "pnpm": "11.23.0"
  }
}
```

The existing Node 24 constraint remains unchanged. The exact pnpm pin makes local containers, production builds, and CI use the same CLI behavior. Corepack is the version-aware launcher inside Node 24 images; contributors do not manage a global pnpm version on the host.

`client/pnpm-lock.yaml` becomes the only tracked client dependency lockfile. `client/package-lock.json` must be removed in the same pull request after a clean frozen pnpm install succeeds.

### Lockfile conversion

The initial pnpm lockfile must be produced with `pnpm import` from the existing `client/package-lock.json`. This preserves the current resolved graph as closely as the package managers' different resolution models allow.

The migration must not run a general dependency update. Direct dependency ranges in `client/package.json` remain unchanged unless pnpm's strict layout proves that client code or tooling imports an undeclared transitive dependency. In that case, the dependency is added directly at a version compatible with the already resolved graph, and the reason is recorded in the pull-request summary.

The lockfile is accepted only when all of the following are true:

- `pnpm install --frozen-lockfile` succeeds from a clean environment;
- a second frozen install produces no manifest or lockfile changes;
- the application verification commands have parity with the npm baseline;
- no direct dependency was upgraded merely because the package manager changed.

### pnpm project settings and build-script policy

pnpm 11 reads project settings from `pnpm-workspace.yaml`, even for a single-package root. `client/pnpm-workspace.yaml` must therefore contain project settings only; omitting a `packages` list keeps `client/` as the sole package.

Dependency install scripts are denied unless reviewed. The current npm lockfile identifies build-script candidates including `esbuild`, `@parcel/watcher`, `unrs-resolver`, and the optional macOS-only `fsevents`. The implementation must use `pnpm ignored-builds` and clean Docker builds to verify the final list, then commit explicit `allowBuilds` decisions. Required cross-platform tooling may be allowed; irrelevant optional tooling such as `fsevents` in the Linux container may be denied. A blanket setting that runs every dependency script is not acceptable.

The final policy must satisfy these rules:

- every dependency build script discovered during a clean install has an explicit allow or deny decision;
- esbuild and other approved native tooling work in both development and production builds;
- newly introduced build-script dependencies remain blocked until reviewed;
- `shamefullyHoist`, permissive node-linker modes, and global script approval are not used as migration shortcuts.

### Client scripts

Scripts should call local package binaries directly where npm adds no useful behavior. Package-manager chaining must use pnpm.

Required corrections include:

- replace the current `"dev": "npm run vite"` indirection with the local `vite` binary, because no `vite` script exists;
- change `api:generate` to run the Orval and typecheck steps through pnpm while preserving the required sequential `composer openapi` then Orval order;
- replace `npx chromatic` with the locally installed `chromatic` binary;
- retain existing public script names so contributor and CI workflows do not change unnecessarily.

After migration, client scripts must not invoke `npm`, `npx`, `yarn`, or another package manager.

## Container Design

### Development image

`client/.docker/Dockerfile` remains based on `node:24-alpine`. Its dependency layer must copy the exact files that control pnpm resolution:

```text
package.json
pnpm-lock.yaml
pnpm-workspace.yaml
```

The image then enables Corepack, activates the manifest-pinned package manager, and runs `pnpm install --frozen-lockfile` before copying application source. The default command and Compose overrides use `pnpm run`.

The Dockerfile must not use `COPY package*.json`, because that pattern describes the npm-era lockfile arrangement and would no longer copy the pnpm lockfile or settings file. It also must not install pnpm globally with an unpinned `npm install -g pnpm`.

### Compose and dependency volumes

`client/docker-compose.yml` retains the existing services, ports, networks, bind mount, and separate named `node_modules` volumes:

- `react-app` continues to expose Vite on port 3000;
- `storybook` continues to expose Storybook on port 6006;
- application source remains bind-mounted at `/app`;
- dependency links remain isolated inside each service's `/app/node_modules` named volume.

pnpm's virtual store lives under `node_modules/.pnpm`, so it remains inside the container-managed volume rather than being written into the Windows checkout. The content-addressable package store is an implementation detail inside the image/container and must not be bind-mounted to the host in this first migration.

Existing named volumes contain npm's layout and Docker does not replace the contents of a non-empty named volume merely because an image was rebuilt. The migration therefore requires this one-time reset from `client/`:

```bash
docker compose down --volumes
docker compose up -d --build
```

This removes only reproducible client dependency volumes declared by `client/docker-compose.yml`; it does not remove backend databases or application data. The same reset is the canonical recovery when a pulled lockfile change leaves either client dependency volume stale.

### Production image

`client/.docker/Dockerfile.production` keeps its current multi-stage shape:

1. build the React application in `node:24-alpine`;
2. copy the generated `/app/build` output into the nginx runtime image.

Only the Node build stage adopts Corepack and pnpm. It must use the exact manifest and lockfile inputs, a frozen install, and `pnpm run build`. Build arguments, Vite environment variables, the non-empty API URL assertion, nginx template, output path, exposed port, and runtime image remain unchanged.

Docker's existing dependency layer remains the first caching mechanism: source-only changes must not invalidate dependency installation. A BuildKit pnpm-store cache mount or `pnpm fetch` flow may be evaluated later if measured build times justify it; neither is required for correctness or for this migration.

## GitHub Actions Design

`.github/workflows/frontend-ci.yml` must migrate both jobs that install client dependencies: `verify` and `publish`.

Each job must:

1. check out the repository;
2. use the official `pnpm/setup` action with Node 24;
3. point the action explicitly at `client/package.json` and `client/pnpm-lock.yaml`, because the repository root has no `package.json`;
4. resolve the pnpm version from the exact `packageManager` field;
5. cache the pnpm content-addressable store, not `node_modules`;
6. run an explicit `pnpm install --frozen-lockfile` from `client/`;
7. run the existing Orval, typecheck, and build scripts with pnpm.

The action should not hide dependency installation inside setup. An explicit install step keeps the workflow readable and makes frozen-lockfile failures appear under the existing install responsibility.

The following behavior remains unchanged:

- workflow triggers and path filters;
- Node major version 24;
- required GitHub repository variables and secrets;
- sequential Orval generation before typecheck/build;
- production image construction and smoke tests;
- GHCR authentication, tags, publishing, and Render deploy hook.

The migration is not complete while any GitHub Actions client step still depends on `npm ci`, `npm run`, npm cache settings, or `client/package-lock.json`.

## Contributor Workflow

### Container-first setup

The normal setup remains:

```bash
cd client
docker compose up -d --build
```

On the first checkout after the migration, or after switching from an npm-built branch, use the one-time volume reset documented above. Contributors do not run `corepack enable` on the host for the normal container workflow; that occurs in the Docker image.

### Command translation

Commands executed inside the client container change as follows:

| npm-era command | pnpm command | Purpose |
| --- | --- | --- |
| `npm install` | `pnpm install` | Install and update the local lockfile during intentional dependency work. |
| `npm ci` | `pnpm install --frozen-lockfile` | Reproduce the committed lockfile in Docker and CI. |
| `npm run build` | `pnpm run build` | Run a package script. |
| `npx <tool>` | `pnpm exec <tool>` | Run a binary already installed in the client. |
| `npx <temporary-package>` | `pnpm dlx <temporary-package>` | Download and run a one-off package without adding it. |
| `npm install <package>` | `pnpm add <package>` | Add a production dependency. |
| `npm install -D <package>` | `pnpm add -D <package>` | Add a development dependency. |
| `npm uninstall <package>` | `pnpm remove <package>` | Remove a dependency. |

Repository documentation should prefer `docker compose exec react-app pnpm run ...` for the container-first workflow. Short `pnpm run ...` examples may remain where the surrounding text explicitly says the command is being run inside `client/` or its container.

### Concepts contributors need to learn

- **Corepack is a launcher, not another dependency manager.** Inside the Node 24 image it reads `packageManager` and activates the exact pnpm version.
- **The lockfile is authoritative.** Docker and CI use `--frozen-lockfile`, so manifest/lock drift fails rather than being repaired silently.
- **The store and `node_modules` are different.** pnpm stores package contents once per environment and links the project-specific dependency graph into `node_modules`.
- **Strict dependency visibility is intentional.** Code may import only packages declared by the relevant package. A missing import is fixed in `package.json`, not through hoisting.
- **Install scripts are an allowlist decision.** When a new dependency asks to run a build script, it must be reviewed before approval.
- **This is not a pnpm workspace migration.** Recursive and workspace-filter commands are unnecessary for the single `client/` package.
- **Do not mix package managers.** Running npm in `client/` can create an unreviewed `package-lock.json`; that file must not be committed.

Optional native Node development is not an acceptance requirement. A contributor who deliberately chooses it must use Node 24 and the manifest-pinned pnpm version, but the repository's canonical setup remains Docker.

## Documentation Scope

The migration must update maintained instructions that operate on the React client, including:

- root `README.md` frontend setup and command examples;
- root `AGENTS.md` package-manager and schema-generation guidance;
- `client/README.md` setup, command, CI, and Orval guidance;
- `client/AGENTS.md` commands or package-manager policy if present;
- maintained docs, migration backlogs, and repository skills that prescribe client npm commands;
- `.dockerignore` and `.gitignore` npm-specific log/lockfile assumptions where they apply to the client.

Backend guidance that intentionally operates `processor-api/package.json` remains npm-based and must not be mass-replaced. Historical prose that does not instruct a current client workflow may remain unchanged, but any command intended to be copied and run against `client/` must use pnpm.

## Migration Sequence Within the Pull Request

The change is atomic at merge time, but implementation should proceed in verifiable checkpoints:

1. Record the npm baseline for install, Orval, typecheck, tests, builds, and Docker smoke behavior.
2. Pin pnpm and import the existing client lockfile without upgrading dependencies.
3. Review dependency build scripts and commit the pnpm project policy.
4. Convert package scripts and prove a clean frozen install.
5. Migrate the development Dockerfile and Compose commands, then recreate dependency volumes.
6. Migrate the production Dockerfile and run the production-image smoke test.
7. Migrate both GitHub Actions jobs, including explicit client paths and pnpm-store caching.
8. Update maintained client-facing documentation and ignore rules.
9. Remove `client/package-lock.json` and scan for remaining active client npm assumptions.
10. Run the complete acceptance suite and compare it with the recorded baseline.

There is no supported dual-lockfile or dual-package-manager state after the pull request merges.

## Verification and Acceptance Criteria

### Lockfile and policy

- `client/package.json` pins Node 24 and pnpm 11.23.0.
- `client/pnpm-lock.yaml` is tracked and stable across repeated frozen installs.
- `client/package-lock.json` is absent.
- Root and `processor-api/` lockfiles are unchanged.
- The dependency build-script allow/deny policy is explicit and has no unreviewed required scripts.
- No permissive hoisting or run-all-build-scripts setting is introduced.

### Development containers

From `client/`, after intentionally removing the old dependency volumes:

```bash
docker compose up -d --build
docker compose exec react-app pnpm --version
docker compose exec react-app pnpm install --frozen-lockfile
docker compose exec react-app pnpm run orval:file
docker compose exec react-app pnpm run typecheck
docker compose exec react-app pnpm run test
docker compose exec react-app pnpm run build
docker compose exec storybook pnpm run build-storybook
```

Expected results:

- the reported pnpm version is `11.23.0`;
- both services remain running and expose their existing ports;
- frozen installation changes neither manifest nor lockfile;
- Orval produces no unexpected generated-client drift;
- typecheck and both builds pass;
- Vitest has the same or better result than the pre-migration baseline, with any unrelated baseline failures reported precisely rather than attributed to pnpm.

### Production image and CI

- A clean production Docker build succeeds with the existing required build arguments.
- The nginx image passes the workflow's existing root-page and nested-route smoke checks.
- Both dependency-installing GitHub Actions jobs use pnpm and the pnpm lockfile.
- The pull-request workflow completes with the same verification, image build, and smoke coverage as before.
- Publishing and deployment behavior is structurally unchanged and remains restricted to the existing branch/event conditions.

### Repository consistency

- Active commands that target `client/` contain no `npm`, `npm ci`, `npm run`, or `npx` usage.
- Any remaining npm references are demonstrably scoped to the backend package, the untouched root lockfile, or non-operational history.
- `git diff --check` passes.
- The pull-request summary lists any direct dependency declaration added because pnpm exposed an undeclared import.

## Risks and Mitigations

| Risk | Mitigation |
| --- | --- |
| Existing code imports an undeclared transitive dependency. | Keep strict linking and add the dependency directly; do not enable broad hoisting. |
| A required native package cannot run its install script. | Review `pnpm ignored-builds`, allow that named package explicitly, and repeat the clean container build. |
| Old npm `node_modules` volumes hide the image's pnpm layout. | Require the one-time scoped Compose volume reset. |
| Lockfile conversion changes transitive peer resolution. | Import rather than update, compare the baseline, and run Orval, typecheck, tests, and both builds. |
| CI setup looks for a root manifest that does not exist. | Configure explicit `client/package.json` and `client/pnpm-lock.yaml` paths in the setup action. |
| Docker dependency layers are invalidated unnecessarily. | Copy only manifest, pnpm lockfile, and pnpm settings before installing; copy source afterward. |
| Contributors mix npm and pnpm after migration. | Remove the client npm lockfile, update runnable docs, and document pnpm as the sole supported client manager. |
| A pnpm-specific compatibility escape hatch masks a real problem. | Require evidence for any exception and prefer package-specific fixes; broad compatibility settings are outside the approved design. |

## Rollback

Rollback is a source-control operation: revert the migration pull request, then recreate the client dependency volumes so they are initialized from the restored npm-based image.

No database, Redis, uploaded content, or user data is changed by this migration. The only deleted Docker volumes are the two reproducible client `node_modules` volumes declared by `client/docker-compose.yml`.

## Official References

- [pnpm installation and Node compatibility](https://pnpm.io/installation)
- [pnpm package manifest settings](https://pnpm.io/package_json)
- [pnpm project settings](https://pnpm.io/settings)
- [pnpm dependency build approvals](https://pnpm.io/cli/approve-builds)
- [pnpm lockfile import](https://pnpm.io/cli/import)
- [Corepack package-manager version management](https://github.com/nodejs/corepack)
- [Official pnpm GitHub Actions setup](https://github.com/pnpm/setup)
