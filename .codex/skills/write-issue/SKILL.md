---
name: write-issue
description: Write a new GitHub issue for japanese-vma, or check and fix an existing one, so an agent or a person can deliver it without asking questions. Produces numbered acceptance criteria, an explicit out-of-scope list, parent and blocking links, a risk line and triage labels. Use when the user asks to file, write, draft, split, triage or "make ready" an issue, epic, slice or follow-up, when a bug report needs assessing, or when another skill stops because a linked issue has no acceptance criteria.
---

# Write issue

The issue body is the contract. `review-branch` checks code against its acceptance criteria, `create-pull-request` shows evidence for each one, and CI fails a PR whose issues have none. Plans and specs are not kept as repo docs, so anything decided belongs in the issue.

Read `docs/agents/issue-tracker.md` (tracker conventions) and `docs/agents/triage-labels.md` (labels) first.

## Modes

- **Write:** draft a new issue, an epic plus sub-issues, or a follow-up from the conversation.
- **Check:** given `#N`, score it, list what is missing, and propose an edited body. Details in [references/check-mode.md](references/check-mode.md).

## The shape

```markdown
Parent: #<epic>

Stable ID: `<EPIC-NN>`. Blocked by: #<n> (or "none").

## Problem
Current behaviour and who it hurts, in 2–4 sentences.

## Desired behaviour
What is true when this is done, described as behaviour, not layers.

## Scope
- **In:** …
- **Out:** … (at least one line: this is what stops scope creep in review)

## Key interfaces
Routes, endpoints, components, use cases, by name. Avoid file paths and line numbers; they go stale.

## Acceptance criteria
- [ ] AC-1 <observable outcome>
- [ ] AC-2 …

## Risk
Criticality: low | medium | high · Door: one-way | two-way · Touches: contract / migrations / auth / queues / none

## Notes
Decisions, links, screenshots.
```

Omit `Parent`, `Stable ID` and `Blocked by` for a standalone issue. Omit empty optional sections (Key interfaces, Notes); never omit Scope, Acceptance criteria or Risk.

An **epic** uses Problem, Design (decisions shared by every slice), a Slices table (Stable ID, issue, one line), Acceptance criteria for the whole epic, and Out of scope.

## Acceptance criteria rules

- Each criterion is observable and independently verifiable: someone can check it without reading your mind.
- 3 to 7 per slice. More than 7 means the issue should be split.
- Test expectations go inside a criterion ("… and a feature test covers the 403 case").
- Never standard procedure: "PR opened", "CI green", "code reviewed", "tests pass" on their own are not criteria. Reject them and explain why.
- Number them `AC-1`, `AC-2`, … so reviews can cite `#N/AC-k`.

## Slicing rules

- One vertical slice per issue: it changes behaviour end to end and can be demonstrated alone.
- Sized for one commit. An epic is delivered as one branch, one commit per sub-issue, one PR into `develop`.
- Order slices so each one builds on the last; record that with `Blocked by`.

## Labels

- Exactly one state: `needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`.
- Exactly one category: `bug` or `enhancement`.
- Area labels on top as they apply: `frontend`, `backend`, `ci`, `documentation`, `epic`, …
- `ready-for-agent` requires acceptance criteria and an Out line. Review follow-ups start as `needs-triage`.

## Publish

1. Show the full title, body and labels. For an epic, show every sub-issue.
2. Wait for the user to confirm. Creating or editing an issue is visible to others.
3. Write each body to a file and create from Bash, where `gh` lives at `/c/Program Files/GitHub CLI/gh.exe`:
   - `gh issue create --repo av3000/japanese-vma --title "…" --label a,b --body-file body.md`
   - Sub-issues: add `--parent <epic>`. Order: add `--blocked-by <n>[,<n>]`.
   - An epic's Slices table needs the sub-issue numbers: create the epic, then the sub-issues, then edit the epic body.
4. Titles: `[<STABLE-ID>] <outcome>` for slices, `[<EPIC>] <outcome>` for epics, a plain outcome otherwise.
5. No attribution footer.

## Editing an existing issue

Keep the existing body exactly as it is, byte for byte, apart from the sections you are adding or fixing. Fetch it with `gh issue view <n> --json body --jq .body > body.md`, edit the file, show the result, and send it back with `gh issue edit <n> --body-file body.md` after confirmation. Never rewrite the issue's intent.

## Done when

- Every section of the shape is present, and every criterion passes the rules above.
- Labels follow the rules above.
- The user has confirmed, and the issue URL (or URLs) is reported back.
