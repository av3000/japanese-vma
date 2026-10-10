# Agent workflow: which skill, when

This guide maps which skills to use, in what order, for each kind of work in this repo. It applies to Claude Code and Codex alike. It is written for the person driving the agent; the agent reads it too.

- **A single skill's behaviour** is defined by its `SKILL.md` (`.claude/skills/<name>/SKILL.md`, mirrored to `.codex/skills/`). If this guide and a `SKILL.md` disagree, the `SKILL.md` is right. Fix this guide.
- **Repo rules** live in the `AGENTS.md` files. Root `AGENTS.md` has a short "Agent workflow" section that points here.
- **Keeping this current:** CI (`scripts/lint-agent-skills.mjs`) fails when a skill in `.claude/skills/` is missing from the table below, or when a row marked `repo` names a skill that does not exist. Adding, renaming or removing a skill updates this guide in the same change.

## The skills at a glance

| Skill | Source | Use it to |
| --- | --- | --- |
| `grilling` | Matt Pocock plugin | Get interviewed until the idea is sharp, one question at a time |
| `write-issue` | repo | Write an issue with numbered acceptance criteria, or check and score an existing one |
| `tdd` | Matt Pocock plugin | Build one behaviour at a time, red → green → refactor |
| `review-branch` | repo | Review the branch against its issues and the repo rules, with typed and rated findings |
| `create-pull-request` | repo | Open the PR into `develop`, with an acceptance-criteria evidence table, risk, screenshots and verification |
| `post-review-to-pr` | repo | Put a `review-branch` report on the PR as a pending review with inline comments |
| `address-pr-review` | repo | Work through review comments on your PR: fix, clarify or decline each one, and reply to every thread |
| `diagnosing-bugs` | Matt Pocock plugin | Hard bugs: build a loop that reproduces the failure before theorising, then fix it with a regression test |
| `prototype` | Matt Pocock plugin | Answer a design question with throwaway code |
| `research` | Matt Pocock plugin | Collect facts from primary sources into a cited file (it runs a background agent, so it costs more) |
| `codebase-design` | Matt Pocock plugin | Design a deeper module: interface, seam, depth |
| `improve-frontend-codebase-architecture` | repo | Find frontend deepening candidates: shallow route, hook and API modules |
| `writing-for-agents` | Matt Pocock plugin | Write or edit a skill, an `AGENTS.md`, or this guide |
| `wizard` | Matt Pocock plugin | Steps only a human can do: dashboards, secrets, one-off cutovers |
| `resolving-merge-conflicts` | Matt Pocock plugin | An in-progress merge or rebase conflict |
| `/code-review`, `/security-review`, `/simplify` | Claude Code built-ins | Bug hunting, a security pass, a clean-up pass. These are optional extras next to `review-branch`. |

**Coming next (epic #502):** `frontend-standards` and `backend-standards`, citable rules with IDs and impact levels. Until they land, `review-branch` cites `AGENTS.md` sections for Standards findings.

## The main flow: idea → merged

```
grilling ─► write-issue ─► per slice: tdd ─► commit ─► review-branch ─► create-pull-request
 (talk)     (epic + slices)                                │                   │
                                                           ▼                   ▼
                                              follow-ups → write-issue   post-review-to-pr ─► address-pr-review ─► merge
                                              (needs-triage, own issue)        (optional)                         close issues by hand
```

### 1. Shape the work

1. **Sharpen the idea.** Run `grilling` until every open question has an answer. If an answer needs runnable code (a state model, a UI you have to see), detour through `prototype` and bring back what you learned.
2. **Write it down.** Run `write-issue`.
   - A multi-slice build becomes an epic plus sub-issues, each with a Stable ID (for example `HARNESS-03`), `Parent`, `Blocked by` and acceptance criteria.
   - A small change is one issue.
   - Read the drafts before you confirm. **This is where scope is decided.** Anything not in an issue's acceptance criteria is scope creep later.
3. **Keep steps 1–2 in one session.** The issues should come straight out of the reasoning, not out of a summary of it.

### 2. Build, one slice at a time

1. **Start each slice from its issue,** not from the earlier conversation. The issue is self-contained, so you can `/clear` between slices.
2. **Drive `tdd`.** If `tdd` hits a bug that resists a first look, switch to `diagnosing-bugs` and come back once it is fixed.
3. **Run the checks the issue names,** using the lanes in `client/AGENTS.md` and `processor-api/AGENTS.md`.
4. **Commit once per slice:**
   ```
   HARNESS-03: remove finished migration skills and Spatie skills

   Refs #505
   ```
   - **First line:** `<Stable ID>: <outcome>`.
   - **Body:** `Refs #N`, never `Closes`: PRs into `develop` never auto-close issues.
   - **No attribution footer.**
5. **Found something outside the slice?** File it with `write-issue` as `needs-triage` and keep going. Never fold it into this branch.

### 3. Review before anyone else does

1. **Run `review-branch` on the finished branch.** It numbers every acceptance criterion and marks it Met, Partial, Missing or Wrong. It labels each finding `[Type · Criticality]`:
   - **Type** is what the finding is measured against: **Spec** (an acceptance criterion), **Standards** (a written repo rule), or **Judgement** (opinion, with reasoning).
   - **Criticality** is **Critical** (blocks the merge), **Warning** (fix now, or defer with a linked issue), or **Suggestion** (optional).
   - The verdict equals the highest criticality present.
2. **Fix Critical and Warning findings.** Run it again until the verdict is *Ready* or *Ready with suggestions*.
3. **For a large branch,** run `/code-review` or `/security-review` as well when the change touches auth, payments or untrusted input. Those passes hunt bugs; `review-branch` checks the spec and the rules.

### 4. Open the PR and close the loop

1. **Run `create-pull-request`.**
   - It stops if a linked issue has no acceptance criteria.
   - It maps each criterion to the commit, test or screenshot that proves it, states the risk, attaches screenshots for anything visible, and lists only checks that actually ran.
2. **Optionally, run `post-review-to-pr`** to leave the `review-branch` report on the PR as a record. It creates a pending review and asks before submitting.
3. **When comments arrive** (yours, CI's, or a person's), run `address-pr-review`.
   - It reads every thread first, then sorts each one into **Fix**, **Clarify** or **Decline**, and shows you the table before touching code.
   - It makes the fixes in one commit (`HARNESS: address the review on #NNN`) and replies to every thread.
4. **After merge, close the issues by hand.** The PR hand-off lists them.
5. **Spend five minutes on a retro.**
   - A mistake a tool could catch becomes a lint rule or a test.
   - A recurring judgement call becomes a written rule: in the scoped `AGENTS.md` today, and in the standards skills once epic #502 lands.
   - A skill that misled the agent gets its `SKILL.md` fixed with `writing-for-agents`.

## Other ways in

| Your situation | Route |
| --- | --- |
| A bug report or request arrives from someone else | `write-issue` in check mode scores it as Ready, Borderline or Blocked and asks the blocking questions. Once Ready, it joins the main flow at step 2. |
| Something is broken right now | `diagnosing-bugs` → regression test → `write-issue` (if no issue exists yet) → main flow from step 2.3 |
| Review comments are waiting on your PR | `address-pr-review` |
| Someone else's PR, or a dependency bump, needs a review | `review-branch <PR number>`, then `post-review-to-pr` |
| A design question can't be settled by talking | `prototype` |
| You need outside facts first (a library, an API, a hosting choice) | `research`, then take the file into `grilling` |
| Code is hard to change and you want it healthier | `improve-frontend-codebase-architecture` or `codebase-design` → `write-issue` → main flow |
| A step needs a human: dashboard, secret, Render or GCP console | `wizard` |
| You are writing or changing a skill, an `AGENTS.md` or this guide | `writing-for-agents`. Edit `.claude/skills/`, then run `bash scripts/sync-agent-skills.sh` and `node scripts/lint-agent-skills.mjs`. |
| A merge or rebase stopped with conflicts | `resolving-merge-conflicts` |

## Context hygiene

A **phase** is a chunk of work: shaping, one slice, the review. Decide what to do with the context **between** phases, never in the middle of one. Take the first answer that fits:

1. **Continue** if the next phase needs this one's reasoning word for word (shaping → first slice), or if there is plenty of room left.
2. **`/clear`** if what came before is disposable. Between slices it usually is, because the issue carries everything.
3. **Hand off** (write a short file) only when the work moves to a different tool (Claude ↔ Codex), a different directory, or a different person.
4. **A subagent** only for a tightly scoped task that can run unattended, and only when you've asked for one. Subagents multiply token use.
5. **`/compact`** otherwise, with an instruction about what to keep: `/compact next: review the branch against #501`.

## Getting the most out of it

- **The issue is the contract.** If the plan changes mid-build, edit the issue's acceptance criteria first, then the code. A review can only check what the issue says.
- **Name the skill when it matters.** Skills trigger from their descriptions, but saying "run review-branch" removes any guessing.
- **Standing behaviour goes in this repo, not in plugin files.** A plugin update overwrites any edit to a plugin's `SKILL.md`. Put rules in `AGENTS.md` or a repo skill instead.
- **Make the machine enforce what can be checked mechanically:** lint, tests, CI (the issue-link check, the skill drift check). Skills are for judgement.
- **Trust, but check the evidence.** Every Critical or Warning finding cites `file:line` and quotes the rule or criterion it breaks. If the quote doesn't say what the finding claims, decline it.
- **Disagreeing is allowed.** Decline a review comment with a reason rather than "fixing" something that isn't broken.

## It's working if

- Every PR into `develop` links issues whose acceptance criteria each map to evidence in the PR body.
- Review findings are labelled `[Type · Criticality]`, and the PR's risk line matches what reviewers actually worried about.
- Follow-ups exist as their own `needs-triage` issues, not as stray changes in unrelated PRs.
- The same review finding doesn't come back epic after epic. Either a rule or a lint check absorbed it.
- You rarely re-explain a convention to an agent. If you do, it belongs in an `AGENTS.md` file or a skill.

## Where this comes from

- **Matt Pocock's skills:**
  - `ask-matt`: the main flow plus on-ramps, context hygiene, and phase boundaries.
  - `retro`: mechanical mistakes become checks, judgement calls become standards.
- **testdouble/han:**
  - `docs/workflows.md` and the how-to guides: skill docs own a single skill, guides own the chains; happy path first; decision points written inline.
  - The review and posting flow.
- **Other references** (a private skills collection):
  - `address-pr-review`: Fix / Clarify / Decline, reply to every thread, warning signs of agreeing just to please.
  - `task-triage-criteria`: Ready / Borderline / Blocked triage.
