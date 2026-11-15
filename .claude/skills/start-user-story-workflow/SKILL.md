---
name: start-user-story-workflow
description: Deliver one japanese-vma user story issue as a reviewed PR into its epic branch. Takes the story issue number and the epic branch; reads the issue and the scoped AGENTS.md, writes an implementation plan with superpowers writing-plans, builds it test-first with superpowers executing-plans (or subagent-driven-development for independent tasks), runs the repo checks, commits with the story's Stable ID, opens the PR into the epic branch with create-pull-request, reports back, and later answers the review with address-pr-review. Appends its actions to the epic run log. Use when the user or the epic agent says "/start-user-story-workflow", "deliver story #N", "build sub-issue #N into the epic branch" or "implement the next story". A whole epic is start-epic-workflow; a standalone issue straight into develop follows docs/agents/workflow.md instead.
---

# Start user story workflow

You are the **story session**: you build one story and own its PR until it is merged. Usually the epic agent (`start-epic-workflow`) started you and waits for your report; sometimes the maintainer runs you directly.

Inputs: `<issue> <epic-branch>`, for example `661 feature/issue-659-translations`, plus the run log path if the epic agent gave one. If the issue or the branch is missing, ask once.

`gh` is not on PATH. Call it from Bash exactly as `'/c/Program Files/GitHub CLI/gh.exe' <args>`, single-quoted and never through a variable, so the project's permission rules match and the PR and review writes run without a prompt. Repo: `av3000/japanese-vma`.

## 1. Set up

1. Check `git branch --show-current`. You should be on a fresh story branch cut from the tip of `origin/<epic-branch>`. If you are on the epic branch itself, `develop` or `master`, create a story branch from `origin/<epic-branch>` first: nothing is committed on the epic branch directly.
2. Read, in this order: the issue (`gh issue view <issue>`), its parent epic, the root `AGENTS.md` and the scoped `AGENTS.md` for every area the issue touches, any planning document the epic links (for example `docs/<feature>/implementation-plan.md` and its section for this story), and `docs/agents/local-environment.md` for a fresh worktree.
3. If the issue has no `## Acceptance criteria`, stop and report: you cannot meet criteria nobody wrote.
4. Append a row to the run log (`../start-epic-workflow/references/run-log.md` says where and how). If no epic agent gave you a log path, derive it from the parent epic's number.

## 2. Plan

Run `superpowers:writing-plans` with the issue as the spec. Two changes to its defaults:

- **Save the plan outside the repo**, in the session's scratchpad (or `/tmp` when there is none), not in `docs/superpowers/plans/`. Story plans are working notes; the repo keeps docs only when they are needed.
- **Map every task to the acceptance criteria** it proves, so nothing in the plan is unasked-for and no criterion is left without a task.

Then post the plan's summary in your reply: files, tasks, the test that proves each acceptance criterion, and the checks you will run. Do not wait for approval; build it.

## 3. Build

- **Default: `superpowers:executing-plans`, inline.** One context, TDD per task, the cheapest path.
- **Subagents only when they pay:** switch to `superpowers:subagent-driven-development` only when the plan has two or more tasks that touch separate files and do not depend on each other. Then use at most 3 implementer subagents at once, each `model: "sonnet"` with `effort: "medium"`, and do the rest inline.
- Build test-first, one behaviour at a time (`tdd`), whichever mode you use.
- **Pushing the story branch is part of this job.** Both execution skills stop before a push to a shared branch; this skill authorises pushing your own story branch with an explicit refspec (`git push -u origin <story-branch>:<story-branch>`). It never authorises pushing the epic branch, `develop` or `master`, or merging anything.
- A finding outside the story goes to `write-issue` as `needs-triage`, never onto this branch.

## 4. Check and commit

1. Run the checks the repo requires for what you touched, from the scoped `AGENTS.md` and the epic's plan: targeted backend tests in a Docker test lane named after the story (`jvma<epic>-<nn>`), Pint on changed files, the contract regeneration when `api.json` changes, and the frontend type check, lint and targeted tests when `client/` changes. No PHPStan and no full suite unless the issue asks for them.
2. A check that cannot run (Docker down, sandbox limits) is an environment limit: try the fix in `docs/agents/local-environment.md` once, then report it as not run. Never call it passing.
3. Mark the requirement rows the story delivers as Delivered if the epic keeps a requirements traceability table.
4. Commit once: first line `<STABLE-ID>: <outcome>`, body `Refs #<issue>`, no attribution trailers or footers.

## 5. Open the PR and report

1. Push the story branch and open the PR with `create-pull-request`, with two changes: the base is `<epic-branch>`, not `develop`, and the title is `[<STABLE-ID>] <outcome>`. Keep its acceptance-criteria evidence table, risk line, verification section and screenshots for anything visible.
2. Log the push and the PR.
3. **End your turn with the report.** The epic agent receives only the first 400 characters, so they carry what it needs, in this order: the PR number and URL, the branch, the commit SHA, and any check not run. Details go after that, or on the PR. Report by ending your turn, not by messaging: ending the turn is what wakes the epic agent.

## 6. Address the review

When asked to run `/address-pr-review <pr>`:

1. Run `address-pr-review`. A `/code-review` comment that describes a real bug counts as a Warning. Suggestions you judge not worth it are declined with a reason. Out-of-scope requests become `needs-triage` issues linked from the reply.
2. All fixes go in one commit: `<STABLE-ID>: address the review on #<pr>`, body `Refs #<issue>`, no attribution. Re-run the checks for what you touched, push, then reply to every thread and resolve it.
3. Write the story's retro in the run log: one to three bullets on what went well, what went wrong and what was missing.
4. End your turn with: the fix commit SHA, the Fix / Clarify / Decline counts, and any follow-up issue numbers.

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
