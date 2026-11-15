---
name: start-epic-workflow
description: Deliver a whole japanese-vma epic, story by story, while the maintainer is away. Takes the epic issue number and the epic branch (already cut from develop), picks the next open user story by its Blocked-by order, runs each one in its own story session with start-user-story-workflow, has a separate session review the story PR, sends the fixes back, squash-merges the story into the epic branch, closes the issue, and repeats until every story is done; then opens the epic PR into develop and stops. Keeps an append-only run log of every agent action. Use when the user says "start the epic", "run the epic workflow", "deliver epic #N", "orchestrate the stories", "work through the sub-issues of #N" or "/start-epic-workflow". One story on its own is start-user-story-workflow.
---

# Start epic workflow

You are the **epic agent**: you coordinate, you never write product code. Each story is built by its own story session and reviewed by its own review session. GitHub is the record of progress, and the run log is the record of what every agent did.

Inputs: `<epic-issue> <epic-branch>`, for example `659 feature/issue-659-translations`. If either is missing, ask once.

`gh` is not on PATH. Call it from Bash exactly as `'/c/Program Files/GitHub CLI/gh.exe' <args>`, single-quoted and never through a variable: the project's permission rules match that spelling, so the GitHub writes this loop makes run without a prompt. A merge whose PR targets `develop` or `master` is denied by the repo's merge guard hook (guard-agent-merge.mjs in the root scripts folder) whatever you do. Repo: `av3000/japanese-vma`. The code blocks below write `gh` for short.

This skill needs the Claude Code desktop app's session tools (`mcp__ccd_session__start_session`, `SendMessage`). Load them with `ToolSearch` if they are deferred. Codex has no equivalent; there, stop and say so.

## 1. Check before the first story

1. `git fetch origin`. The epic branch must exist on `origin`; stop if it does not. It was cut from `develop` by the maintainer; never create it yourself.
2. Read the epic (`gh issue view <epic-issue>`) and list its sub-issues with their state and their Blocked-by links:
   ```bash
   gh api graphql -F owner=av3000 -F name=japanese-vma -F n=<epic-issue> -f query='
   query($owner:String!, $name:String!, $n:Int!) { repository(owner:$owner, name:$name) { issue(number:$n) {
     subIssues(first:50) { nodes { number title state
       blockedBy(first:20) { nodes { number state } } } } } } }'
   ```
   If `blockedBy` is not available, read the `Blocked by:` line in each issue body instead.
3. Every open sub-issue needs an `## Acceptance criteria` checklist. If one has none, stop and offer `write-issue` for it: an agent cannot meet criteria nobody wrote.
4. Check that `.claude/settings.json` holds the allow rules for the `gh` writes and the merge guard hook. Agents cannot add them: the maintainer does. If they are missing, say once that every GitHub write will wait for a person's approval, and go on.
5. Open the run log (`references/run-log.md` says where and how). Create it with its header if it does not exist. If it does, this is a resumed run: read it to see where the last run stopped.

Write a short status for the maintainer: the stories, which are done, which is next, and the branch. Then start; do not wait for a reply.

## 2. The loop, one story at a time

Re-read the sub-issues from GitHub at the start of every pass, never from memory: a restart or a compacted context must resume at the right story.

1. **Pick the next story:** the lowest-numbered open sub-issue whose blockers are all closed. If a story's PR is already open from an earlier run, resume that story at the step it reached instead of starting it again. If open stories remain but none is unblocked, stop and report the cycle.
2. **Refresh the epic branch:** merge `origin/develop` into it (a merge, never a rebase: the branch is shared) and push with `git push origin <epic-branch>:<epic-branch>`. Skip when `develop` has not moved. A merge conflict is a stop.
3. **Start the story session:** `start_session` with `initiation: "user_asked"`, `context: "fresh"`, `model: "claude-opus-5-5"`, `effort: "medium"`, `use_worktree: true`, `source_branch: <epic-branch>`, `branch_name: <stable-id lowercased>`, `name: "<stable-id lowercased>-impl"`, `wake_when_done: true`. Brief:
   > Run `/start-user-story-workflow <issue> <epic-branch>`. Run log: `<absolute log path>`. When you finish, end your turn with the report the skill describes; do not message me.

   Add the story's section header and a start row to the log.
4. **Wait** for the session to end its turn. Its report starts with the PR number, the branch, the commit SHAs and any check it could not run. Check the PR exists, targets the epic branch, and every commit subject starts with the story's Stable ID. If the session stopped instead, see section 3.
5. **Start the review session:** `start_session` with `initiation: "user_asked"`, `context: "fresh"`, `model: "claude-sonnet-5-5"`, `effort: "medium"`, `use_worktree: true`, `source_branch: <story branch>`, `name: "<stable-id lowercased>-review"`, `wake_when_done: true`. Brief:
   > Read-only review of PR #<pr> (issue #<issue>). Run `review-branch` for the PR and post it with `post-review-to-pr`, then run `/code-review high <pr> --comment`. Change no code. Append your rows to the run log `<path>`. End your turn with: the finding counts by criticality, the verdict, and the number of inline comments.

   It must be a session, not a subagent: `/code-review` starts its own subagents, and a subagent cannot.
6. **Send the review back:** `SendMessage` to the story session: "Run `/address-pr-review <pr>`. Treat every `/code-review` comment that describes a real bug as a Warning." Wait for its report: the fix commit SHA and the Fix / Clarify / Decline counts.
7. **Merge:** check the PR first. No unresolved thread may be a Critical or a Warning that was declined without a reason, the fix commit must start with the Stable ID, and CI, if it ran, must be green. Then:
   ```bash
   gh pr merge <pr> --repo av3000/japanese-vma --squash --subject "<STABLE-ID>: <outcome> (#<pr>)" --body "Refs #<issue>"
   gh issue close <issue> --repo av3000/japanese-vma --comment "Delivered in #<pr>."
   ```
   Keep the story branch: the PR keeps the separate implementation and review commits. Tick the story in the epic body only if the epic uses a checklist.
8. **Log** the merge and close, add your line to the story's retro, and go back to step 1.

Never archive, stop or delete the sessions you start. The maintainer reads them later.

## 3. When to stop and ask

Stop, write one short summary for the maintainer, log the stop, and end your turn when:

- a session hits the same failure twice;
- a required check cannot run (Docker, a lane, CI);
- a Critical finding is declined, or the story session and the review disagree on one;
- a story needs a scope change against its issue, or an issue has no acceptance criteria;
- merging `develop` into the epic branch conflicts;
- the weekly token quota is close to running out.

Do not stop between stories to ask "continue?". The maintainer reviews merged story PRs afterwards.

If the app holds session notes back after many automated turns in a row, the loop pauses until the maintainer writes. That is expected: when they write "continue", re-read GitHub and resume.

## 4. When every story is done

1. Merge `origin/develop` into the epic branch one last time and push.
2. Finish the run log with its `## Run retro` section.
3. Open the epic PR into `develop` with `create-pull-request`:
   - an acceptance-criteria table for the epic, linking each story PR;
   - `Refs #<epic>` plus every story issue;
   - the follow-ups the stories filed;
   - `## Agent run log`: the condensed log (`references/run-log.md`, "At the end").
4. Stop. Never merge into `develop`: that is the maintainer's review.

## Done when

- Every sub-issue is closed with a comment linking its merged PR.
- The epic PR into `develop` is open, and nothing was merged into `develop`.
- The run log has a row for every story step, a retro per story and a run retro.
