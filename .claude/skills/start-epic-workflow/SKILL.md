---
name: start-epic-workflow
description: Deliver a whole japanese-vma epic, story by story, while the maintainer is away. Takes the epic issue number and the epic branch (already cut from develop), picks the next open user story by its Blocked-by order, runs each one in its own story session with start-user-story-workflow, has a separate session review the story PR, sends the fixes back, squash-merges the story into the epic branch, closes the issue, and repeats until every story is done; then opens the epic PR into develop and stops. Keeps an append-only run log of every agent action. Use when the user says "start the epic", "run the epic workflow", "deliver epic #N", "orchestrate the stories", "work through the sub-issues of #N" or "/start-epic-workflow". One story on its own is start-user-story-workflow.
---

# Start epic workflow

You are the **epic agent**: you coordinate, you never write product code. Each story is built by its own story session and reviewed by its own review session. GitHub is the record of progress, and the run log is the record of what every agent did.

Inputs: `<epic-issue> <epic-branch>`, for example `659 feature/issue-659-translations`. If either is missing, ask once. If `<epic-branch>` is `develop`, `master` or `main`, stop: story PRs must never target a protected branch.

Optional settings, written as `key=value` after the inputs:

| Setting | Default | Meaning |
|---|---|---|
| `story-model` | `claude-sonnet-5-5` | Model of each story session |
| `story-effort` | `high` | Effort of each story session |
| `review-model` | `claude-sonnet-5-5` | Model of each review session |
| `review-effort` | `medium` | Effort of each review session |
| `stories` | every open sub-issue | Comma-separated issue numbers. The run stops after these, for a retro |
| `parallel` | `2` | Most story sessions at once (§2 step 1); `1` runs strictly one at a time |
| `reviewers` | `max(parallel, ceil(stories / 4))` | Review sessions in the pool (§1 step 8); each reviews up to 4 story PRs, in turn |

Call `gh` exactly as `'/c/Program Files/GitHub CLI/gh.exe' <args>`: single-quoted, as the command itself, one GitHub call per Bash call. The project permission rules match only that spelling. A `PATH` export, a variable, a bare `gh`, or a call inside `$(...)` or after `&&` falls through to the auto-mode classifier, which denies GitHub writes in unattended runs. Always pass a PR by its number, never its URL or branch, and never merge through the GitHub MCP tools. The repo's merge guard hook (guard-agent-merge.mjs in the root scripts folder) denies a merge whose PR targets `develop` or `master`, the GraphQL and REST merge and ref-writing calls, and `'/c/Program Files/GitHub CLI/gh.exe' pr edit --base`. It is accident prevention for an agent following this skill, not a security boundary; branch protection on GitHub is. Repo: `av3000/japanese-vma`.

This skill needs the Claude Code desktop app's session tools (`mcp__ccd_session__start_session`, `SendMessage`). Load them with `ToolSearch` if they are deferred. Codex has no equivalent; there, stop and say so.

**Unattended.** The sessions you start run with nobody watching. Every brief says so, and the repo skills they use (`post-review-to-pr`, `address-pr-review`) have an unattended mode for it. Never relay an approval to a session: an approval only counts when the maintainer gives it in that session.

**Session starts.** The app refuses `start_session` on any turn the maintainer did not write: a session's note, a finished background command, another session's message. No setting changes that. `SendMessage` and `gh` are not refused. So start every session the run needs in one turn, while the maintainer's message is the turn (§1 step 8), as standby sessions, and drive them afterwards with `SendMessage`. All later work goes to a session from that pool. In epic #693 each later start paused the run until the maintainer typed "continue"; with the pool, one message carried the rest of the run.

## 1. Check before the first story

1. **Your model.** `start_session` caps a child's model and effort at yours, so the sessions silently run on less when you run on less. Check your own model and effort (`mcp__ccd_session_mgmt__get_session` with `self`). They must be at least the highest `*-model` and `*-effort` setting. If they are not, stop and say so.
2. `git fetch origin`. The epic branch must exist on `origin`; stop if it does not. It was cut from `develop` by the maintainer; never create it yourself, and never check it out in your own worktree.
3. Read the epic (`'/c/Program Files/GitHub CLI/gh.exe' issue view <epic-issue>`) and list its sub-issues with their state and their Blocked-by links:
   ```bash
   '/c/Program Files/GitHub CLI/gh.exe' api graphql -F owner=av3000 -F name=japanese-vma -F n=<epic-issue> -f query='
   query($owner:String!, $name:String!, $n:Int!) { repository(owner:$owner, name:$name) { issue(number:$n) {
     subIssues(first:50) { nodes { number title state
       blockedBy(first:20) { nodes { number state } } } } } } }'
   ```
   If `blockedBy` is not available, read the `Blocked by:` line in each issue body instead.
4. Every open sub-issue needs an `## Acceptance criteria` checklist. If one has none, stop and offer `write-issue` for it: an agent cannot meet criteria nobody wrote. An epic criterion that one story has to prove (a transcript, a screenshot) belongs in that story's criteria too; a brief alone gets it done late or not at all.
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
7. **Inherited sessions.** The app wakes you only when a session *you* started ends a turn. Check every open story PR on the epic branch and the session the log names for it (`mcp__ccd_session_mgmt__list_sessions` with `linked: true` shows which sessions are yours). For a session another agent started:
   - **idle:** do not message it. Start a resume session for its story (below);
   - **still running:** `SendMessage` it with `notify_when_idle: true` and no message, then wait for that notice.

   **A resume session** is a story session from the pool (step 8) for a PR that already exists. The PR's head branch is usually still checked out in the old session's worktree, even when that session is gone, and Git refuses to check out one branch in two worktrees. So give it `source_branch: <PR head branch>` and `branch_name: <PR head branch>-resume`, and add the resume line to its `GO` message (§2 step 3). It pushes to the PR's head branch with an explicit refspec. Never archive or stop the old session to free the branch: the maintainer reads it later.

   Never wait on a session that cannot wake you. In the epic #659 run, this cost about 8 idle hours.
8. **Start the pool.** Still in the maintainer's turn, refresh the epic branch (§2 step 2), then start every session the run needs in one message, so one wake covers them all: one story session per story left (resume sessions from step 7 count), `reviewers` review sessions and one verification session. Each gets `initiation: "user_asked"`, `context: "fresh"`, `use_worktree: true`, `source_branch: <epic-branch>`, `wake_when_done: true`, and this `prompt`: "Standby for epic #<epic>. Reply `standing by` and end your turn. Read nothing and build no lane until the epic agent sends `GO …` by message; then do what it says."
   - **Story session:** `model: <story-model>`, `effort: <story-effort>`, `branch_name: <stable-id lowercased>`; `name`: a short form of the Stable ID plus `-impl`, at most 24 characters of `[a-z0-9-]`, for example `tr-02-impl`; `background` (required, at least 80 characters): "Unattended run of start-epic-workflow for epic #<epic> on branch <epic-branch>, story <STABLE-ID> (#<issue>). Run log: <absolute log path>. Nobody is watching: use the skills' unattended modes, and stop with a report instead of asking. Call every `gh` command as `'/c/Program Files/GitHub CLI/gh.exe' <args>`, one GitHub write per Bash call, with no PATH export, no `$(...)` and no `&&` chains: the permission rules match only that spelling; `git push` goes in its own Bash call too. Known traps, detailed in the story skill §3: write scripts and multi-line files with the Write tool, not heredocs; `spawn EPERM` and a Vitest run that stops after one file are sandbox limits, reported as not run; an endpoint change goes backend source, then `composer openapi`, then `npm run orval:file`; the issue's acceptance criteria are the scope; anything visible needs Storybook stories and screenshots; a dependency upgrade is no reason for a wider test run. Wait for lane builds and CI inside your turn, and stop your monitors before you end it: ending a turn wakes the epic agent."
     The spelling rule and the traps are in the brief as well as in the skills on purpose: the first run lost a review post without the rule.
   - **Review session:** `model: <review-model>`, `effort: <review-effort>`, `name: "<epic short id>-rev<k>"`, and a `background` like the story session's, saying "read-only reviewer for several story PRs" instead of naming one story.
   - **Verification session:** like a review session, with `name: "<epic short id>-verify"` and a `background` saying "read-only verification of the whole epic".

   Log one row per session with its `local_…` id and the story or role it is for: every later step reaches it only by that id. If a start is refused (the app's session cap), start the rest as far as allowed, log which roles have no session, and carry on with the pool you have; the maintainer's next message starts the missing ones.

Write a short status for the maintainer: the stories, which are done, which is next, and the branch. Then start; do not wait for a reply.

## 2. The loop

Re-read the sub-issues from GitHub at the start of every pass, never from memory: a restart or a compacted context must resume at the right story. With the `stories` setting, consider only those issues. When they are all closed, go to §4: run step 1 (the final refresh) and step 2, then stop there instead of opening the epic PR.

1. **Pick the next story:** the lowest-numbered open sub-issue whose blockers are all closed. If no open sub-issue is left, go to §4 step 1: that refresh merges a `develop` that moved during the run, so the verification checks what the epic PR will contain. If open stories remain but none is unblocked, stop and report the cycle.
   - **Already open from an earlier run.** If the story's PR is already open, resume it instead of starting it. Find it with `'/c/Program Files/GitHub CLI/gh.exe' pr list --repo av3000/japanese-vma --base <epic-branch> --state open --json number,title,body,headRefName`, and count only a PR whose title starts with `[<STABLE-ID>]` or whose body says `Refs #<issue>`; any other open PR on the epic branch is not this story's, and is a stop. Resume it at the step it reached: message the story session by the `local_…` id the log recorded, following §1 step 7 when another agent started it; if that session is gone and the pool has no resume session for it, stop (§3).
   - **A second story at once.** Start a second story when all of these hold:
     - `parallel` is 2 or more;
     - another open story is also unblocked;
     - the two touch separate areas, for example one only `processor-api/` and the other only `client/`, judged from their issues and the epic's plan;
     - neither blocks the other.

     When in doubt, run them one at a time. Each story gets its own sessions and its own log section.
   - **Merges stay one at a time.** Merge one story, refresh the epic branch (step 2), then check that the other PR is still `MERGEABLE` and contains `origin/<epic-branch>`. If it does not, send its story session: "Merge `origin/<epic-branch>` into your branch (a merge, not a rebase), re-run your checks, push, and report." Then run the gate again.
2. **Refresh the epic branch.** The pool's worktrees are cut from the **local** `<epic-branch>`, but the story merges happen on GitHub, so bring the local ref up to `origin` on every pass:
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
3. **Release the story session.** First add the story's section header to the log, so the session's first row lands under it. Then `SendMessage` to the story's `local_…` id from the pool: "GO story <STABLE-ID> (#<issue>), epic #<epic>, branch `<epic-branch>`. Your worktree was cut earlier: run `git fetch origin`, then `git merge --ff-only origin/<epic-branch>` (you have no commits yet). Run `/start-user-story-workflow <issue> <epic-branch>`. Run log: `<absolute log path>`. Add any correction the run has learned since the pool started. When you finish, end your turn with the report the skill describes; do not message me." For a resume session (§1 step 7), replace the fast-forward line with: "PR #<pr> already exists on branch `<PR head branch>`; continue from where it is. Your local branch is `<PR head branch>-resume`. First run `git fetch origin` and `git merge --ff-only origin/<PR head branch>`. Push only with `git push origin HEAD:<PR head branch>`, never to the `-resume` branch."

   Log the release row with the session's `local_…` id. A `SendMessage` that fails (the session was archived or stopped) is a stop.
4. **Wait** for the session to end its turn. Do not parse its report for the PR: the 400 characters that reach you can be cut short. Look it up instead:
   ```bash
   '/c/Program Files/GitHub CLI/gh.exe' pr list --repo av3000/japanese-vma --base <epic-branch> --state open --json number,headRefName,headRefOid,title
   ```
   Exactly one open PR must have a title starting with `[<STABLE-ID>]`. Any open PR that belongs to no running story is a stop. Check that its commit subjects all start with the story's Stable ID. Use the report only for the checks the session could not run. If the session stopped instead, see section 3.
5. **Send the review:** `SendMessage` to the story's reviewer from the pool. Stories go to the reviewers in turn, in release order (story 1 to `rev1`, story 2 to `rev2`, and so on, wrapping round), so each reviews up to 4 PRs. A reviewer still busy picks the message up when its turn ends. The message:
   > GO review PR #<pr> (issue #<issue>), epic branch `<epic-branch>`, story session `<story local_… id>`. Check out the PR head detached: `git fetch origin pull/<pr>/head`, then `git checkout --detach FETCH_HEAD`. First run the checks for the paths the PR touches yourself, never only read them: the targeted backend tests (`vendor/bin/phpunit <paths>`, not `composer test`) in your own Docker test lane `jvma<epic>r<k>` plus Pint when `processor-api/` changed; tsc, ESLint, the targeted Vitest tests and `npm run build-storybook` when `client/` changed. Build the lane on your first PR and keep it up for the next ones: it mounts your worktree, so it tests whatever you have checked out. Wait for the build inside your turn. Put the result lines in the review body. A check that fails is a finding; a check that cannot run is reported as not run. Then run `review-branch` for the PR, post it with `post-review-to-pr` in its unattended mode, and run `/code-review high <pr> --comment`. Change no code. Append your rows to the run log `<path>`. Then `SendMessage` the story session: "Run `/address-pr-review <pr>` in its unattended mode. Treat every `/code-review` comment that describes a real bug as a Warning." End your turn with: the finding counts by criticality, the verdict, the checks you ran with their results, the number of inline comments, and whether the story session got the message.

   The reviewer must be a session, not a subagent: `/code-review` starts its own subagents, and a subagent cannot. It is fresh to every author it reviews, which is what self-review in a fresh session asks for.
6. **The review goes back without you.** The reviewer hands it to the story session directly, so the story session is already working when the reviewer stops and you are not woken in between. Send the story session that message yourself only if the reviewer's report says it could not. Wait for the story session's report, then read the PR again for the fix commit and the thread replies.
7. **Merge:** check the PR first. Merge only when all of these hold:
   - A review from the review session is posted on the PR. No posted review means no merge, whatever the session reported.
   - The story session's Fix / Clarify / Decline table is in the run log, and every Critical in it is a Fix.
   - Every Decline of a Warning has a reply on its thread that gives a reason: the rule or criterion it does not break, or the failing scenario that does not exist. "Not needed" or "out of scope" without a linked `needs-triage` issue does not count.
   - The fix commit starts with the Stable ID and touches the files the Fix rows cite (`'/c/Program Files/GitHub CLI/gh.exe' pr view <pr> --json commits,files`).
   - CI is green. Backend CI, Frontend CI (for `client/` or `api.json` changes), the issue-link check and the agent-skills check run on PRs into `feature/**` branches. Read them with `'/c/Program Files/GitHub CLI/gh.exe' pr checks <pr> --repo av3000/japanese-vma`. The story session waits for its own CI, so a run is rarely still pending here. When one is, wait inside your turn with one foreground `'/c/Program Files/GitHub CLI/gh.exe' pr checks <pr> --repo av3000/japanese-vma --watch --fail-fast` (Bash `timeout: 600000`), never a background watcher: when every session is idle, nothing else wakes you. A red run goes back to the story session as a Fix. A PR with no checks at all, when its paths should trigger them, is a stop: the epic branch may be missing the workflow change from `develop`.

   If one fails, send the story session one more `/address-pr-review <pr>` naming the gap; a second failure is a stop. Then:
   ```bash
   '/c/Program Files/GitHub CLI/gh.exe' pr merge <pr> --repo av3000/japanese-vma --squash --subject "<STABLE-ID>: <outcome> (#<pr>)" --body "Refs #<issue>"
   '/c/Program Files/GitHub CLI/gh.exe' issue close <issue> --repo av3000/japanese-vma --comment "Delivered in #<pr>."
   ```
   Keep the story branch: the PR keeps the separate implementation and review commits. Tick the story in the epic body only if the epic uses a checklist.
8. **Log** the merge and close, add your line to the story's retro, and go back to step 1. Release the next story in the same turn: a merge and a `SendMessage` need no maintainer message.

Never archive, stop or delete the sessions you start. The maintainer reads them later.

## 3. When to stop and ask

Stop, write one short summary for the maintainer, log the stop, and end your turn when:

- a session hits the same failure twice;
- a session you need has no `local_…` id in the pool (its start was refused, or it was archived or stopped): never start one later and never do its work in your own context; say which one, and the maintainer's next message starts it;
- the review session ends without a posted review (a duplicate or pending review it would not overwrite, or a failed post);
- a required check cannot run (Docker, a lane, CI);
- a Critical finding is declined, or the story session and the review disagree on one;
- a story needs a scope change against its issue, or an issue has no acceptance criteria;
- the permission rules are missing, or a session reports a permission prompt it could not pass;
- merging `develop` into the epic branch conflicts, or the local epic branch cannot be fast-forwarded;
- the weekly token quota is close to running out.

Do not stop between stories to ask "continue?". The maintainer reviews merged story PRs afterwards.

If the app holds session notes back after many automated turns in a row, the loop pauses until the maintainer writes. That is expected: when they write "continue", re-read GitHub and resume. It is no reason to start sessions later; fewer wakes (reviewers handing off directly, sessions waiting inside their turn) make it rarer.

## 4. When every story is done

1. Refresh the epic branch one last time (§2 step 2).
2. **Check the whole epic.** Each story was tested on its own, and nothing has run them together yet. `SendMessage` the verification session from the pool (§1 step 8):
   > GO verify. Run `git fetch origin`, then `git checkout --detach origin/<epic-branch>`. Read-only verification of the epic branch `<epic-branch>`. List the paths every merged story PR touched (`'/c/Program Files/GitHub CLI/gh.exe' pr view <n> --json files` for each: <PR numbers>). Run their backend tests in your own Docker test lane, plus Pint. If `client/` changed, also run tsc, ESLint, the Vitest tests for the touched paths and `npm run build-storybook`. Change no code. Append your rows to the run log `<path>`. End your turn with each check and its result line.

   A failing check is a stop: report it, and do not open the epic PR. With the `stories` setting, stop after this step and write the run retro.
3. Finish the run log with its `## Run retro` section.
4. Open the epic PR into `develop` with `create-pull-request`:
   - an acceptance-criteria table for the epic, linking each story PR;
   - `Refs #<epic>` plus every story issue;
   - the follow-ups the stories filed;
   - `## Open review threads`: the unresolved threads on the merged story PRs, each with its link and the one-line reason from the story session's reply. The maintainer settles them in one place instead of hunting through closed PRs. In its unattended mode `address-pr-review` resolves every Fix thread and leaves every Decline thread unresolved, so an unresolved thread is a Decline, or a Clarify still waiting for an answer; say which. List them for each story PR:
     ```bash
     '/c/Program Files/GitHub CLI/gh.exe' api graphql -F owner=av3000 -F name=japanese-vma -F n=<pr> -f query='
     query($owner:String!, $name:String!, $n:Int!) { repository(owner:$owner, name:$name) { pullRequest(number:$n) {
       reviewThreads(first:100) { nodes { isResolved path
         comments(first:20) { nodes { body url } } } } } } }'
     ```
     Keep the nodes with `isResolved: false`. Link the first comment's `url`; the last comment's `body` is the reason;
   - the verification session's result lines;
   - `## Agent run log`: the condensed log (`references/run-log.md`, "At the end").
5. Stop. Never merge into `develop`: that is the maintainer's review.

## Done when

- Every sub-issue is closed with a comment linking its merged PR.
- The whole-epic verification passed, and its result lines are in the epic PR.
- The epic PR into `develop` is open, lists every unresolved review thread, and nothing was merged into `develop`.
- The run log has a row for every story step, a retro per story and a run retro.
