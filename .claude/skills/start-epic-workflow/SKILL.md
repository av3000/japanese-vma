---
name: start-epic-workflow
description: Deliver a whole japanese-vma epic, story by story, while the maintainer is away. Takes the epic issue number and the epic branch (already cut from develop), picks the next open user story by its Blocked-by order, runs each one in its own story session with start-user-story-workflow, has a separate session review the story PR, sends the fixes back, squash-merges the story into the epic branch, closes the issue, and repeats until every story is done; then opens the epic PR into develop and stops. Keeps an append-only run log of every agent action. Use when the user says "start the epic", "run the epic workflow", "deliver epic #N", "orchestrate the stories", "work through the sub-issues of #N" or "/start-epic-workflow". One story on its own is start-user-story-workflow.
---

# Start epic workflow

You are the **epic agent**: you coordinate, you never write product code. Each story is built by its own story session and reviewed by its own review session. GitHub is the record of progress, and the run log is the record of what every agent did.

Inputs: `<epic-issue> <epic-branch>`, for example `659 feature/issue-659-translations`. If either is missing, ask once. If `<epic-branch>` is `develop`, `master` or `main`, stop: story PRs must never target a protected branch.

`gh` is not on PATH. Call it from Bash exactly as `'/c/Program Files/GitHub CLI/gh.exe' <args>`, single-quoted and never through a variable: the project's permission rules match that spelling, so the GitHub writes this loop makes run without a prompt. Always pass a PR by its number, never its URL or branch, and never merge through the GitHub MCP tools. The repo's merge guard hook (guard-agent-merge.mjs in the root scripts folder) denies a merge whose PR targets `develop` or `master`, the GraphQL and REST merge and ref-writing calls, and `gh pr edit --base`. It is accident prevention for an agent following this skill, not a security boundary; branch protection on GitHub is. Repo: `av3000/japanese-vma`. The code blocks below write `gh` for short.

This skill needs the Claude Code desktop app's session tools (`mcp__ccd_session__start_session`, `SendMessage`). Load them with `ToolSearch` if they are deferred. Codex has no equivalent; there, stop and say so.

**Unattended.** The sessions you start run with nobody watching. Every brief says so, and the repo skills they use (`post-review-to-pr`, `address-pr-review`) have an unattended mode for it. Never relay an approval to a session: an approval only counts when the maintainer gives it in that session.

## 1. Check before the first story

1. **Your model.** `start_session` caps a child's model and effort at yours. You must run on Opus 5.5 at medium effort or higher, or the story sessions silently run on less. If you run on a smaller model, stop and say so.
2. `git fetch origin`. The epic branch must exist on `origin`; stop if it does not. It was cut from `develop` by the maintainer; never create it yourself, and never check it out in your own worktree.
3. Read the epic (`gh issue view <epic-issue>`) and list its sub-issues with their state and their Blocked-by links:
   ```bash
   gh api graphql -F owner=av3000 -F name=japanese-vma -F n=<epic-issue> -f query='
   query($owner:String!, $name:String!, $n:Int!) { repository(owner:$owner, name:$name) { issue(number:$n) {
     subIssues(first:50) { nodes { number title state
       blockedBy(first:20) { nodes { number state } } } } } } }'
   ```
   If `blockedBy` is not available, read the `Blocked by:` line in each issue body instead.
4. Every open sub-issue needs an `## Acceptance criteria` checklist. If one has none, stop and offer `write-issue` for it: an agent cannot meet criteria nobody wrote.
5. **Permission rules and the merge guard.** Each session reads the `.claude/settings.json` committed in its own worktree, so the rules must be committed, not only present in a local checkout. If they are on `origin/develop` but not yet on the epic branch, the refresh in §2 step 2 brings them in; do that first. Then check `origin/<epic-branch>`:
   - `git show origin/<epic-branch>:.claude/settings.json` allows the `gh` writes (`pr create`, `pr merge`, `issue close`, the review calls) and `git push origin`, denies the GitHub MCP merge tool, and runs the merge guard as a `PreToolUse` hook with the matcher `Bash|PowerShell`.
   - `git cat-file -e origin/<epic-branch>:scripts/guard-agent-merge.mjs` succeeds. The hook ends in `|| exit 2`, so a missing script would block every Bash call.
   - The guard denies a test payload. This needs no network:
     ```bash
     git show origin/<epic-branch>:scripts/guard-agent-merge.mjs > "<scratchpad>/guard-check.mjs"
     printf '%s' '{"tool_input":{"command":"gh api graphql -f query=\"mutation { mergePullRequest(input:{}) { clientMutationId } }\""}}' \
       | node "<scratchpad>/guard-check.mjs" | grep -q '"permissionDecision":"deny"'
     ```

   If any check fails, **stop**: without the rules the first GitHub write in a story session waits at a prompt and nothing wakes you, and without the guard nothing stops a merge into `develop`. Your own checkout needs the same file. Agents cannot add it; the maintainer commits it.
6. Open the run log (`references/run-log.md` says where and how). Create it with its header if it does not exist. If it does, this is a resumed run: read it to see where the last run stopped, and which session ids it logged.

Write a short status for the maintainer: the stories, which are done, which is next, and the branch. Then start; do not wait for a reply.

## 2. The loop, one story at a time

Re-read the sub-issues from GitHub at the start of every pass, never from memory: a restart or a compacted context must resume at the right story.

1. **Pick the next story:** the lowest-numbered open sub-issue whose blockers are all closed. If open stories remain but none is unblocked, stop and report the cycle. If its PR is already open from an earlier run, resume it. Find it with `gh pr list --base <epic-branch> --state open --json number,title,body`, and count only a PR whose title starts with `[<STABLE-ID>]` or whose body says `Refs #<issue>`; any other open PR on the epic branch is not this story's, and is a stop. Resume it at the step it reached: message the story session by the `local_…` id the log recorded; if that session is gone, start a fresh story session on the PR's head branch (`source_branch` set to it) with the brief's resume line.
2. **Refresh the epic branch.** `start_session` cuts a worktree from the **local** `<epic-branch>`, but the story merges happen on GitHub, so bring the local ref up to `origin` on every pass:
   ```bash
   git fetch origin
   WT="$(git worktree list --porcelain | awk -v b="branch refs/heads/<epic-branch>" '/^worktree /{w=substr($0,10)} $0==b{print w}')"
   if [ -z "$WT" ]; then git fetch origin <epic-branch>:<epic-branch>; else git -C "$WT" merge --ff-only origin/<epic-branch>; fi
   ```
   When `git merge-base --is-ancestor origin/develop origin/<epic-branch>` fails, `develop` has moved: merge it (a merge, never a rebase: the branch is shared) in a detached temporary worktree, push the merge commit from your own checkout (worktrees share commits, and the permission rules match `git push origin`), and run the block above again:
   ```bash
   git worktree add --detach "<scratchpad>/epic-merge" origin/<epic-branch>
   git -C "<scratchpad>/epic-merge" merge origin/develop --no-edit
   git -C "<scratchpad>/epic-merge" rev-parse HEAD
   git push origin <that sha>:refs/heads/<epic-branch>
   git worktree remove "<scratchpad>/epic-merge"
   ```
   A merge conflict, or a fast-forward that fails, is a stop. Abort the merge and remove the temporary worktree first.
3. **Start the story session.** First add the story's section header to the log, so the session's first row lands under it. Then `start_session` with:
   - `initiation: "user_asked"`, `context: "fresh"`, `model: "claude-opus-5-5"`, `effort: "medium"`, `use_worktree: true`, `source_branch: <epic-branch>`, `branch_name: <stable-id lowercased>`, `wake_when_done: true`;
   - `name`: a short form of the Stable ID plus `-impl`, at most 24 characters of `[a-z0-9-]`, for example `tr-02-impl`;
   - `background` (required, at least 80 characters): "Unattended run of start-epic-workflow for epic #<epic> on branch <epic-branch>, story <STABLE-ID> (#<issue>). Run log: <absolute log path>. Nobody is watching: use the skills' unattended modes, and stop with a report instead of asking.";
   - `prompt`: "Run `/start-user-story-workflow <issue> <epic-branch>`. Run log: `<absolute log path>`. When you finish, end your turn with the report the skill describes; do not message me." On a resume, add: "The branch and PR #<pr> already exist; continue from where they are."

   Log the start row with the session's `local_…` id from the tool result. A resumed run needs it to reach the session.
4. **Wait** for the session to end its turn. Do not parse its report for the PR: the 400 characters that reach you can be cut short. Stories run one at a time, so look it up:
   ```bash
   gh pr list --repo av3000/japanese-vma --base <epic-branch> --state open --json number,headRefName,headRefOid,title
   ```
   There must be exactly one. Check that its commit subjects all start with the story's Stable ID. Use the report only for the checks the session could not run. If the session stopped instead, see section 3.
5. **Start the review session:** `start_session` with `initiation: "user_asked"`, `context: "fresh"`, `model: "claude-sonnet-5-5"`, `effort: "medium"`, `use_worktree: true`, `source_branch: <story branch>`, `name: "<short id>-review"` (at most 24 characters), `wake_when_done: true`, a `background` like the story session's with "read-only review" in it, and the prompt:
   > Read-only review of PR #<pr> (issue #<issue>). Run `review-branch` for the PR, post it with `post-review-to-pr` in its unattended mode, then run `/code-review high <pr> --comment`. Change no code. Append your rows to the run log `<path>`. End your turn with: the finding counts by criticality, the verdict, and the number of inline comments.

   Log its `local_…` id. It must be a session, not a subagent: `/code-review` starts its own subagents, and a subagent cannot.
6. **Send the review back:** `SendMessage` to the story session's `local_…` id: "Run `/address-pr-review <pr>` in its unattended mode. Treat every `/code-review` comment that describes a real bug as a Warning." Wait for its report, then read the PR again for the fix commit and the thread replies.
7. **Merge:** check the PR first. Merge only when all of these hold:
   - A review from the review session is posted on the PR. No posted review means no merge, whatever the session reported.
   - The story session's Fix / Clarify / Decline table is in the run log, and every Critical in it is a Fix.
   - Every Decline of a Warning has a reply on its thread that gives a reason: the rule or criterion it does not break, or the failing scenario that does not exist. "Not needed" or "out of scope" without a linked `needs-triage` issue does not count.
   - The fix commit starts with the Stable ID and touches the files the Fix rows cite (`gh pr view <pr> --json commits,files`).
   - CI, if it ran, is green (story PRs into an epic branch usually get no CI).

   If one fails, send the story session one more `/address-pr-review <pr>` naming the gap; a second failure is a stop. Then:
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
- `start_session` is refused, for example because the app's session cap is reached: never do the story or the review in your own context instead;
- the review session ends without a posted review (a duplicate or pending review it would not overwrite, or a failed post);
- a required check cannot run (Docker, a lane, CI);
- a Critical finding is declined, or the story session and the review disagree on one;
- a story needs a scope change against its issue, or an issue has no acceptance criteria;
- the permission rules are missing, or a session reports a permission prompt it could not pass;
- merging `develop` into the epic branch conflicts, or the local epic branch cannot be fast-forwarded;
- the weekly token quota is close to running out.

Do not stop between stories to ask "continue?". The maintainer reviews merged story PRs afterwards.

If the app holds session notes back after many automated turns in a row, the loop pauses until the maintainer writes. That is expected: when they write "continue", re-read GitHub and resume.

## 4. When every story is done

1. Refresh the epic branch one last time (§2 step 2).
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
