---
name: start-user-story-workflow
description: Deliver one japanese-vma user story issue as a reviewed PR into its epic branch. Takes the story issue number and the epic branch; reads the issue and the scoped AGENTS.md, writes an implementation plan with superpowers writing-plans, builds it test-first with superpowers executing-plans (with dispatching-parallel-agents for independent tasks), runs the repo checks, commits with the story's Stable ID, opens the PR into the epic branch with create-pull-request, reports back, and later answers the review with address-pr-review. Appends its actions to the epic run log. Use when the user or the epic agent says "/start-user-story-workflow", "deliver story #N", "build sub-issue #N into the epic branch" or "implement the next story". A whole epic is start-epic-workflow; a standalone issue straight into develop follows docs/agents/workflow.md instead.
---

# Start user story workflow

You are the **story session**: you build one story and own its PR until it is merged. Usually the epic agent (`start-epic-workflow`) started you and waits for your report; sometimes the maintainer runs you directly.

Inputs: `<issue> <epic-branch>`, for example `661 feature/issue-659-translations`, plus the run log path if the epic agent gave one. If the issue or the branch is missing, ask once.

Call `gh` exactly as `'/c/Program Files/GitHub CLI/gh.exe' <args>`: single-quoted, as the command itself, one GitHub call per Bash call. The project permission rules match only that spelling. A `PATH` export, a variable, a bare `gh`, or a call inside `$(...)` or after `&&` falls through to the auto-mode classifier, which denies GitHub writes in unattended runs. Always pass a PR by its number. Repo: `av3000/japanese-vma`.

**Unattended.** When the epic agent started you, nobody is watching: never end a turn on a question or a menu. Where a skill you use waits for the user, use its unattended mode, or this skill's override below. Stop with a report (the stop list at the end) instead of asking.

## 1. Set up

1. Check `git branch --show-current`. You should be on a fresh story branch cut from the tip of `origin/<epic-branch>`. If you are on the epic branch itself, `develop` or `master`, create a story branch from `origin/<epic-branch>` first: nothing is committed on the epic branch directly. Then `git fetch origin` and check `git merge-base --is-ancestor origin/<epic-branch> HEAD`. If it fails, your branch was cut from a stale epic branch and is missing earlier stories: while it has no commits of its own, `git reset --hard origin/<epic-branch>`; otherwise rebase it onto `origin/<epic-branch>`.
2. Read, in this order: the issue (`'/c/Program Files/GitHub CLI/gh.exe' issue view <issue>`), its parent epic, the root `AGENTS.md` and the scoped `AGENTS.md` for every area the issue touches, any planning document the epic links (for example `docs/<feature>/implementation-plan.md` and its section for this story), and `docs/agents/local-environment.md` for a fresh worktree.
3. If the issue has no `## Acceptance criteria`, stop and report: you cannot meet criteria nobody wrote.
4. Append a row to the run log (`../start-epic-workflow/references/run-log.md` says where and how). If no epic agent gave you a log path, derive it from the parent epic's number.

## 2. Plan

Run `superpowers:writing-plans` with the issue as the spec. Three changes to its defaults:

- **Save the plan outside the repo**, in the session's scratchpad (or `/tmp` when there is none), not in `docs/superpowers/plans/`. Story plans are working notes; the repo keeps docs only when they are needed.
- **Map every task to the acceptance criteria** it proves, so nothing in the plan is unasked-for and no criterion is left without a task.
- **No commit steps in the tasks.** The story gets one commit at the end (§4), so leave out the template's per-task `git commit` step.

Then post the plan's summary in your reply: files, tasks, the test that proves each acceptance criterion, and the checks you will run. Do not wait for approval; build it.

## 3. Build

- **Default: `superpowers:executing-plans`, inline.** One context, TDD per task, the cheapest path. Three of its steps are replaced; record each as a ruling in its ledger:
  - **No per-task commits.** Leave the work uncommitted between tasks; §4 makes the one commit.
  - **No final whole-branch reviewer.** The review session the epic agent starts is the final review, so a third review would only spend tokens.
  - **No `finishing-a-development-branch`.** Its menu waits for a person; §4 and §5 replace it.
- **Subagents only when they pay:** when the plan has two or more tasks that touch separate files and do not depend on each other, run those tasks in parallel with `superpowers:dispatching-parallel-agents`: at most 3 subagents at once, each `model: "sonnet"` with `effort: "medium"`, told not to commit. Do the rest inline.
- Build test-first, one behaviour at a time (`tdd`), whichever mode you use.
- **Pushing the story branch is part of this job.** Both execution skills stop before a push to a shared branch; this skill authorises pushing your own story branch with an explicit refspec (`git push -u origin <story-branch>:<story-branch>`). It never authorises pushing the epic branch, `develop` or `master`, or merging anything.
- A finding outside the story goes to `write-issue` as `needs-triage`, never onto this branch.
- **Stay inside the surfaces the issue names.** When an issue says "a representative set" or "for example", the list in its acceptance criteria is the scope. Migrating more than that is a follow-up issue, not extra work on this branch.
- **Known traps** (`docs/agents/local-environment.md` has the details):
  - Write scripts and multi-line files with the Write tool, never with a Bash heredoc or `sed`: the Bash tool halves backslashes, which breaks PHP namespaces and regexes.
  - `spawn EPERM` and a Vitest browser run that exits 0 after one file are sandbox limits. Try the documented fix once, then report the check as not run.
  - A change to an endpoint follows `CONTRACT-api-json-orval-workflow` (`backend-standards`), in order: the backend source, then `composer openapi`, then `npm run orval:file`. Never edit `api.json` or the generated client by hand.

## 4. Check and commit

1. Run the checks the repo requires for what you touched, from the scoped `AGENTS.md` and the epic's plan: targeted backend tests in a Docker test lane named after the story (`jvma<epic>-<nn>`), Pint on changed files, the contract regeneration when `api.json` changes, and, when `client/` changes, the frontend type check, ESLint, the targeted Vitest tests and `npm run build-storybook`. No PHPStan and no full suite unless the issue asks for them.
2. A check that cannot run (Docker down, sandbox limits) is an environment limit: try the fix in `docs/agents/local-environment.md` once, then report it as not run. Never call it passing.
3. Mark the requirement rows the story delivers as Delivered if the epic keeps a requirements traceability table.
4. Commit once: first line `<STABLE-ID>: <outcome>`, body `Refs #<issue>`, no attribution trailers or footers. If a skill committed along the way anyway, fold those commits into this one (`git reset --soft origin/<epic-branch>`, then commit) before you push.

## 5. Open the PR and report

1. Push the story branch and open the PR with `create-pull-request`, with these changes:
   - the base is `<epic-branch>`, not `develop`;
   - **replace every `origin/develop` in it with `origin/<epic-branch>`**: the Refs it collects, the diff stat and the overlap check. Against `develop`, the range includes every story already merged into the epic branch, and the PR would claim their issues and criteria;
   - the title is `[<STABLE-ID>] <outcome>`.

   Keep its acceptance-criteria evidence table, risk line and verification section. **Screenshots are required for anything visible, Filament admin pages included, and an unattended run is no exemption.** Take them with Playwright from Node, as `create-pull-request` describes. If they cannot be taken, that is an environment limit: say so in the PR and in your report, never just leave them out. CI runs on PRs into `feature/**` branches too. After opening the PR, read `'/c/Program Files/GitHub CLI/gh.exe' pr checks <pr> --repo av3000/japanese-vma` once. Never use `--watch`: a full CI run outlasts the Bash timeout. A red check is yours to fix before you report. A pending check goes in your report as pending; the epic agent waits for it.
2. Log the push and the PR.
3. **End your turn with the report.** The epic agent receives only the first 400 characters, and your `wrap_up` recap comes first. So the recap starts with `PR #<n> <branch> <sha7>`, and the final message starts with the same line, then any check not run. Details go after that, or on the PR. Report by ending your turn, not by messaging: ending the turn is what wakes the epic agent.

## 6. Address the review

When asked to run `/address-pr-review <pr>`:

1. Run `address-pr-review`, in its unattended mode when the epic agent asked. A `/code-review` comment that describes a real bug counts as a Warning. Suggestions you judge not worth it are declined with a reason. Out-of-scope requests become `needs-triage` issues linked from the reply.
2. All fixes go in one commit: `<STABLE-ID>: address the review on #<pr>`, body `Refs #<issue>`, no attribution. Re-run the checks for what you touched, push, then reply to every thread and resolve it.
3. Write the story's retro in the run log: one to three bullets on what went well, what went wrong and what was missing.
4. End your turn with a recap and message that start with: the fix commit SHA, the Fix / Clarify / Decline counts, and any follow-up issue numbers.

Do not merge the PR and do not close the issue: the epic agent does both after checking the PR.

## Stop and report instead of guessing when

- the same failure happens twice;
- a required check cannot run after one attempt at the documented fix;
- meeting an acceptance criterion would need a change outside the story's scope;
- a Critical review finding does not hold up and you would decline it.

Log the stop, then end your turn with the reason in the first line.

## Done when

- The PR into the epic branch exists, with one implementation commit and at most one review-fix commit, each starting with the Stable ID.
- Every review thread has a reply, and the Fix threads are resolved.
- The run log has your rows and the story's retro.
