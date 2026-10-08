# Issue tracker: GitHub

Issues and planning tickets for this repo live as GitHub issues in `av3000/japanese-vma`.

## Conventions

- Create issues in the GitHub repository, not in GitLab.
- **The issue body is the durable record** of what was asked and agreed. Plans and specs are not kept as long-lived repo docs; the issue holds the decisions, and the PR shows the evidence.
- Local clone remotes may include both GitHub and GitLab, but issue publication targets GitHub.
- Call `gh` by full path on Windows: `C:\Program Files\GitHub CLI\gh.exe` (Bash: `'/c/Program Files/GitHub CLI/gh.exe'`). Pass bodies with `--body-file` from Bash; PowerShell captures can flatten newlines.

## Issue shape

Every issue an agent writes or fixes has this shape. The `write-issue` skill produces it, and the issue forms in `.github/ISSUE_TEMPLATE/` ask for the same fields.

```markdown
Parent: #<epic>            (sub-issues only)
Stable ID: `<EPIC-NN>`     (slices of an epic)
Blocked by: #<n>           (omit if none)

## Problem
## Desired behaviour
## Scope
- In: …
- Out: …                   (at least one line)
## Key interfaces           (names, not file paths or line numbers)
## Acceptance criteria
- [ ] AC-1 <observable outcome>
## Risk
Criticality: low | medium | high · Door: one-way | two-way · Touches: contract / migrations / auth / queues / none
## Notes
```

- Acceptance criteria are observable and independently verifiable, 3 to 7 per slice. Test expectations go inside a criterion. Never use standard procedure as a criterion ("PR opened", "CI green").
- One vertical slice per sub-issue, sized for one commit.

## Epics, sub-issues and blocking

- Create a sub-issue with `gh issue create --parent <epic>`, so it appears under the epic.
- Mark order with `gh issue create --blocked-by <n>[,<n>]`. Also write `Blocked by: #n` in the body, for readers.
- An epic is delivered on one branch (`feature/issue-<epic>-<slug>`), with one commit per sub-issue, as one PR into `develop`.

## Reading an issue

"Fetch the relevant ticket" means `gh issue view <n> --comments`. For an epic, read every sub-issue as well.

## PRs and closing

- Commits and PR bodies use `Refs #N`. PRs into `develop` never auto-close issues; `Closes` only fires on the default branch.
- Close the delivered issues by hand after the PR merges.

## Follow-ups

Anything found mid-work that is outside the current slice becomes its own issue, labelled `needs-triage`, and is linked from the PR's Follow-ups section. It never becomes extra changes on the current branch.

## When a skill says "publish to the issue tracker"

Create a GitHub issue in `av3000/japanese-vma`, in the shape above.
