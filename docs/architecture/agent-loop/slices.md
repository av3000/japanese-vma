# Proposed issues

**Temporary.** These are drafts for `write-issue`, in the repo's issue shape, so they can be filed once the decisions in [README section 10](./README.md#10-decisions-for-you) are made. After they are filed, delete this file: the issues become the record (`docs/agents/issue-tracker.md`).

Two epics, because they are two different kinds of risk:

- **AGENT-READY** (phase 1) changes nothing about who writes code. Every slice is useful even if the loop is never built: faster local checks, a safer `develop`, portable skills.
- **AGENT-LOOP** (phases 2 and 3) starts unattended runs. It is blocked by AGENT-READY.

Each epic is one branch and one PR, one commit per slice (`docs/agents/workflow.md`).

---

## Epic: [AGENT-READY] Make the repo safe and legible for unattended agents

**Problem.** Agent runs today depend on you being present: the environment setup lives in two CI files and two `AGENTS.md` files, the checks run only in CI after a push, the skills assume this Windows machine, nothing stops a malformed issue from being labelled `ready-for-agent`, and `develop` accepts direct pushes while Actions tokens default to write and may approve PRs.

**Design.** CI and agents share one setup action and one verify command, so they cannot disagree about what "green" means. Mechanical rules become scripts and settings; judgement stays in the skills.

| Stable ID | Title |
| --- | --- |
| AGENT-READY-01 | Shared workspace setup action for backend and frontend CI |
| AGENT-READY-02 | `scripts/verify.sh` runs the checks that apply to a diff |
| AGENT-READY-03 | Claude Code hooks: format on edit, verify before stop (delivers #380 part B) |
| AGENT-READY-04 | Portable agent context: `gh` on `PATH` first, a `CLAUDE.md` that imports `AGENTS.md` |
| AGENT-READY-05 | `ready-for-agent` is refused for issues that fail the shape check; `agent:*` labels exist |
| AGENT-READY-06 | Review gate: `develop` ruleset, read-only default token, no PR approval by Actions, agent PRs need an issue link |
| AGENT-READY-07 | Secrets and steering files: deploy hook in a protected environment, `CODEOWNERS`, a path guard for agent PRs |

**Acceptance criteria (epic).**

- [ ] AC-1 A fresh Linux runner and a fresh local worktree reach the same verify result for the same commit.
- [ ] AC-2 An issue that fails the shape check cannot keep the `ready-for-agent` label.
- [ ] AC-3 No workflow token can approve a PR, and a push straight to `develop` is refused.
- [ ] AC-4 No workflow outside the `production` environment can read a deploy secret.

**Out of scope.** Starting any agent run (AGENT-LOOP). SHA pinning of each action (already #484; AGENT-READY-07 only turns on the setting once #484 lands). Dependabot alert triage (#370).

### AGENT-READY-01: Shared workspace setup action

**Problem.** `backend-ci.yml` and `frontend-ci.yml` each repeat the PHP, Composer, Postgres/Redis env, Passport key, Node, `npm ci` and Orval steps. An agent runner would need a third copy, and copies drift.

**Desired behaviour.** A composite action prepares the backend, the frontend, or both. Both CI workflows use it, with no change to what they check.

**Scope.** In: `.github/actions/setup-workspace`, both CI workflows switched to it. Out: changing what CI checks; caching changes beyond what exists today.

**Acceptance criteria.**

- [ ] AC-1 `setup-workspace` takes `backend` and `frontend` inputs and prepares only what is asked for.
- [ ] AC-2 Backend CI and frontend CI use it, and run the same checks as before (same step names after setup).
- [ ] AC-3 The test env values (database, Redis, app key) are set by the action. The `services:` blocks, which GitHub does not allow inside a composite action, stay in the jobs, and each one has a comment naming the other copies.
- [ ] AC-4 `actionlint` passes on the changed workflows.

**Risk.** low · two-way · Touches: CI.

### AGENT-READY-02: `scripts/verify.sh`

**Problem.** The checks a change needs are spread across CI and both `AGENTS.md` files. An agent learns about a failure only after a push, minutes later, and has to guess which local command matches.

**Desired behaviour.** `scripts/verify.sh --changed` looks at the diff against `origin/develop` and runs the checks that apply (table in README section 5.5). It prints one line per check and stops at the first failure with a hint. `--all` runs everything. The backend part runs in the Docker test lane locally (`LANE=<name>`) and directly on a runner.

**Scope.** In: the script, a line in root `AGENTS.md` pointing to it. Out: replacing CI jobs with the script; PHPStan (too slow for the inner loop today).

**Acceptance criteria.**

- [ ] AC-1 A diff touching only `client/` runs typecheck, unit Vitest, the style audit and ESLint on changed files, and nothing from `processor-api/`.
- [ ] AC-2 A diff touching a PHP file runs Pint `--test` on changed files, the affected test folders and the `api.json` drift check.
- [ ] AC-3 A stale `api.json` fails with the same fix hint as backend CI.
- [ ] AC-4 A diff touching `.claude/skills/` runs the skill lint and the Codex mirror check.
- [ ] AC-5 The script exits non-zero on the first failure and prints which check failed.

**Risk.** low · two-way · Touches: none.

### AGENT-READY-03: Claude Code hooks

This is #380 part B. File it as its own issue and link #380, rather than adding a second copy here. Its acceptance criteria are in #380; add one: the `Stop` hook calls `scripts/verify.sh --changed`.

### AGENT-READY-04: Portable agent context

**Problem.** `address-pr-review`, `create-pull-request`, `review-branch` and `docs/agents/issue-tracker.md` name `/c/Program Files/GitHub CLI/gh.exe`. On a Linux runner that path does not exist and `gh` is on `PATH`. The repo also has no `CLAUDE.md`: Claude Code reads `AGENTS.md` by itself only in recent versions and only when no `CLAUDE.md` exists, so an older CLI on a runner would start without the repo's rules.

**Desired behaviour.** Skills say "use `gh`; on this Windows machine it is at …", so they work on both. A one-line `CLAUDE.md` imports `@AGENTS.md`.

**Scope.** In: the repo skills, `docs/agents/`, `CLAUDE.md`. Out: plugin skills (they are installed per job in AGENT-LOOP-02).

**Acceptance criteria.**

- [ ] AC-1 No repo skill tells the agent to call `gh` only by the Windows path.
- [ ] AC-2 `scripts/lint-agent-skills.mjs` fails when a skill names the Windows `gh` path without the `PATH` form.
- [ ] AC-3 The Codex mirror is regenerated and the drift check passes.
- [ ] AC-4 `CLAUDE.md` contains only the `@AGENTS.md` import (and a line saying why), and a Claude Code session started at the repo root lists the root `AGENTS.md` among its loaded memory files.

**Risk.** low · two-way · Touches: none.

### AGENT-READY-05: Issue shape check and agent labels

**Problem.** `ready-for-agent` can be added to any issue. About 80 open issues carry it, some without the shape an unattended run needs.

**Desired behaviour.** When `ready-for-agent` is added, `scripts/check-issue-ready.mjs` checks the shape (README section 5.1). If it fails, the label is swapped for `needs-info` and a comment lists what is missing. The `agent:queued`, `agent:running`, `agent:review` and `agent:stuck` labels exist, and `docs/agents/triage-labels.md` describes them.

**Scope.** In: the script, its workflow, the labels, the triage doc. Out: re-checking the existing 80 issues (a one-off run of the script, reported in the PR); dispatching anything.

**Acceptance criteria.**

- [ ] AC-1 Adding `ready-for-agent` to an issue with fewer than 3 or more than 7 AC lines removes it, adds `needs-info` and comments with the reason.
- [ ] AC-2 The same happens for a missing Scope `Out` line or a missing Risk line. An open `Blocked by` issue does not fail the check (the dispatcher waits for it).
- [ ] AC-3 A well-formed issue keeps the label and gets no comment.
- [ ] AC-4 The script runs locally against an issue number, like `check-pr-issue-link.mjs`.
- [ ] AC-5 The four `agent:*` labels exist with descriptions, and `triage-labels.md` documents who sets each.

**Risk.** low · two-way · Touches: none.

### AGENT-READY-06: Review gate

**Problem.** `develop` is unprotected, Actions tokens default to write, Actions may approve PRs, and the issue-link check skips every `[bot]` author, so a PR opened by an agent app would skip it.

**Desired behaviour.** `develop` changes only through PRs whose checks pass, with your bypass kept. Workflow tokens are read-only unless a workflow asks for more, and cannot approve PRs. The issue-link check skips Dependabot only.

**Scope.** In: the settings (through `wizard`, since only you can change them), `check-pr-issue-link.mjs`. Out: required human approvals (a solo maintainer cannot approve their own PR).

**Acceptance criteria.**

- [ ] AC-1 A direct push to `develop` from a non-bypass actor is refused.
- [ ] AC-2 A PR into `develop` cannot merge while the backend, frontend or issue-link check fails.
- [ ] AC-3 The repository's default workflow permission is read, and "Allow GitHub Actions to create and approve pull requests" no longer allows approval.
- [ ] AC-4 A PR by an agent app with no issue link fails the issue-link check; a Dependabot PR still skips it.
- [ ] AC-5 Every existing workflow still passes after the default token change (each declares the permissions it needs).

**Risk.** medium · two-way · Touches: CI, repository settings.

### AGENT-READY-07: Secrets and steering files

**Problem.** `RENDER_DEPLOY_HOOK_URL` is a repository secret, so any workflow on any branch can name it, including one an agent wrote. The files that steer agents and CI (`.github/**`, `AGENTS.md`, `.claude/**`, `.codex/**`, Dockerfiles, Compose files) have no owner and no guard. In the 2025 Nx attack, a workflow changed on a branch was what leaked the publish token.

**Desired behaviour.** Deploy secrets live in a `production` environment that only `master` can deploy from, with you as the required reviewer. `CODEOWNERS` names you for the steering files. A check fails any PR by the agent's App that touches them.

**Scope.** In: the environment (through `wizard`), the frontend publish job pointed at it, `CODEOWNERS`, the path-guard check, the "require SHA-pinned actions" setting once #484 lands. Out: GitLab CI and the GCP worker secrets, which GitHub never sees.

**Acceptance criteria.**

- [ ] AC-1 `RENDER_DEPLOY_HOOK_URL` exists only as a `production` environment secret, and the repository secret is deleted.
- [ ] AC-2 The frontend publish job still deploys from `master`, after your approval.
- [ ] AC-3 A job on any other branch that names the secret gets an empty value.
- [ ] AC-4 A PR by the agent's App that changes `.github/**`, an `AGENTS.md`, `.claude/**`, `.codex/**`, a Dockerfile or a Compose file fails the path-guard check, naming the file.
- [ ] AC-5 `CODEOWNERS` lists those paths.

**Risk.** medium · two-way · Touches: CI, secrets, repository settings.

---

## Epic: [AGENT-LOOP] Unattended runs from a labelled issue to a draft PR

Blocked by: AGENT-READY.

**Problem.** Every agent run starts and is steered by you in a chat. Well-specified issues wait until you have a free session.

**Design.** README sections 3 to 5. Runs happen on GitHub Actions with `claude-code-action` (decision D1), dispatched only by you with `agent:queued`, one at a time, with caps on turns, minutes, retries and runs per week, and a kill switch.

| Stable ID | Title |
| --- | --- |
| AGENT-LOOP-01 | Dispatcher: owner-only `agent:queued`, WIP limit, `Blocked by`, kill switch |
| AGENT-LOOP-02 | Build run: one issue to a pushed, verified branch with a progress comment |
| AGENT-LOOP-03 | Review run: a fresh context reviews the branch and opens the draft PR |
| AGENT-LOOP-04 | `@claude` from the owner on an agent PR runs `address-pr-review` |
| AGENT-LOOP-05 | CI auto-fix on agent PRs, at most two attempts |
| AGENT-LOOP-06 | Weekly scorecard |

**Acceptance criteria (epic).**

- [ ] AC-1 Three single-slice issues go from `agent:queued` to a draft PR that you merge with at most one fix commit each.
- [ ] AC-2 No agent job can read deploy secrets, push to `develop`, or change `.github/`.
- [ ] AC-3 Setting `AGENT_LOOP_ENABLED=false` stops every trigger.

**Out of scope.** Epics delivered by the loop (phase 4). Nightly triage and drift sweeps (phase 5). Codex or Copilot as engines (D7).

### AGENT-LOOP-01: Dispatcher

**Desired behaviour.** On `agent:queued` (or `workflow_dispatch` with an issue number), the dispatcher starts a run only when: the label was added by the owner, the issue was written by the owner, `AGENT_LOOP_ENABLED` is `true`, fewer than the WIP limit of issues are `agent:running` or `agent:review`, every `Blocked by` issue is closed, and the weekly cap is not reached. Otherwise it leaves the issue queued and says why in a comment. When a run ends, it starts the oldest queued issue.

**Acceptance criteria.**

- [ ] AC-1 `agent:queued` added by anyone but the owner, or on an issue someone else wrote, is removed, with a comment, and starts nothing.
- [ ] AC-2 With one issue already `agent:running` and a limit of 1, a second queued issue waits and starts after the first run ends.
- [ ] AC-3 An issue with an open `Blocked by` issue waits, with a comment naming the blocker.
- [ ] AC-4 `AGENT_LOOP_ENABLED=false` makes the dispatcher exit before starting anything.
- [ ] AC-5 The weekly cap is read from a repository variable and counted from the last seven days of `agent:running` events.

**Risk.** medium · two-way · Touches: CI.

### AGENT-LOOP-02: Build run

**Desired behaviour.** The build run checks out `agent/issue-<n>` from `develop`, prepares the workspace with `setup-workspace`, and runs `claude-code-action` (pinned to a SHA, `base_branch: develop`) with the run prompt (`sketches/run-prompt.md`), the `tdd` plugin, a turn cap, a job timeout, a tool allow-list and an egress allow-list. It acts as the agent's own minimal GitHub App (Contents, Issues, Pull requests), so CI runs on its pushes and it cannot change workflow files. Its only secrets are the App key and a dedicated, spend-capped model API key. It ends with a pushed, verified branch, or `agent:stuck` and a reason.

**Acceptance criteria.**

- [ ] AC-1 A run on a well-formed single-slice issue pushes a branch with one commit per slice, each `<Stable ID>: <outcome>` with `Refs #N`, and `scripts/verify.sh --changed` green on the last one.
- [ ] AC-2 The issue has exactly one progress comment, edited as the run goes, with a link to the run.
- [ ] AC-3 A run that hits the turn cap or the timeout sets `agent:stuck` and writes the reason.
- [ ] AC-4 The prompt contains the issue number and no issue text, and the workflow interpolates no `github.event` text into a `run:` line.
- [ ] AC-5 The job can read no deploy secret, has no `id-token` permission, and a push from it that changes `.github/` is refused.
- [ ] AC-6 Network calls outside GitHub, npm, Packagist and the model API are blocked and appear in the job log.

**Risk.** high · two-way · Touches: CI, secrets.

### AGENT-LOOP-03: Review run

**Problem.** A session that wrote the code reviews it too kindly (README section 5.6).

**Desired behaviour.** When a build run pushes, a second run starts with a fresh context and the review prompt from `sketches/run-prompt.md`. It runs `review-branch`, fixes Critical and Warning findings (at most two rounds), and opens the draft PR with `create-pull-request`.

**Acceptance criteria.**

- [ ] AC-1 The review run starts only after a build run pushed, and shares no conversation with it.
- [ ] AC-2 It opens a draft PR into `develop` whose body maps every AC to evidence and states the `review-branch` verdict, and backend and frontend CI run on it.
- [ ] AC-3 With Critical findings left after two rounds, the PR lists them at the top and the issue is `agent:stuck`; otherwise the issue is `agent:review`.
- [ ] AC-4 Its tool allow-list permits `gh pr create --draft` and not `gh pr merge` or `gh pr review --approve`.

**Risk.** medium · two-way · Touches: CI.

### AGENT-LOOP-04: Review comments

**Acceptance criteria.**

- [ ] AC-1 `@claude` in a comment by the owner on a PR labelled `agent:review` starts `address-pr-review` on that PR.
- [ ] AC-2 The same comment by anyone else starts nothing.
- [ ] AC-3 The run replies to every open thread and pushes at most one fix commit.

**Risk.** medium · two-way · Touches: CI.

### AGENT-LOOP-05: CI auto-fix

**Acceptance criteria.**

- [ ] AC-1 A failed backend or frontend check on an agent PR starts one fix run that reads the failing job log.
- [ ] AC-2 After two fix runs on the same PR, a third failure sets `agent:stuck` instead.
- [ ] AC-3 A fix run never weakens or skips a test to pass; `verify.sh` fails on newly added skips.

**Risk.** medium · two-way · Touches: CI.

### AGENT-LOOP-06: Weekly scorecard

**Acceptance criteria.**

- [ ] AC-1 Once a week, an issue comment (or a pinned issue) lists the measures in README section 9 for the last seven days.
- [ ] AC-2 `agent:stuck` reasons are grouped, and a reason seen twice is flagged.
- [ ] AC-3 It is built from labels, PRs and run logs with `gh`; it needs no other service.

**Risk.** low · two-way · Touches: CI.
