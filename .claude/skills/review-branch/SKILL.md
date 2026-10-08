---
name: review-branch
description: Review the current branch, or a PR by number, against its linked issues' acceptance criteria and this repo's documented rules. Produces findings typed Spec / Standards / Judgement, each rated Critical / Warning / Suggestion, plus a scope-and-risk summary and a merge verdict. Use when the user asks to review a branch, PR, phase or epic, to "check before I open the PR", or to self-review finished work. Posting the result to GitHub is post-review-to-pr; answering review comments is address-pr-review; pure bug hunting is the built-in /code-review.
---

# Review branch

Check a diff against what was asked (the issue) and what the repo says (its rules), and report findings a reviewer can act on. Run inline: do not spawn subagents, and do not invoke other review skills, unless the user asks.

## 1. Fix the diff

1. `git fetch origin`.
2. Branch: diff `origin/develop...HEAD`. PR number: read base and head with `gh pr view <n> --json baseRefName,headRefName,headRefOid`, then diff `origin/<base>...<headRefOid>` (fetch the head first if needed).
3. Size: **S** ≤ 5 files, **M** 6–20, **L** > 20 (`git diff --stat`).
4. Note the areas touched (`client/`, `processor-api/`, CI, docs) and whether the diff touches an API contract (`api.json`, Requests, Resources, generated client), migrations, auth, or queues. These feed the Risk line.

## 2. Load the spec

1. Collect issue numbers from `Refs #N` (and `Closes`/`Fixes`) in `git log origin/develop..HEAD` messages and, for a PR, its body.
2. Read each with `gh issue view <n> --comments` (`gh` is at `/c/Program Files/GitHub CLI/gh.exe`). For an epic, read every sub-issue.
3. Number the criteria per issue: `#N/AC-k`.
4. **No issue, or an issue without acceptance criteria:** stop and offer `write-issue`. Continue only if the user says so, and then write "Spec: not checked (no acceptance criteria)" in the report.

## 3. Load the standards

- Root `AGENTS.md`, plus the scoped `AGENTS.md` for every area the diff touches.
- Standards skills with rule IDs, when present (`frontend-standards`, `backend-standards`).
- `docs/adr/`.
- Skip anything ESLint, tsc, Prettier, Pint or PHPStan already enforce; tooling reports those.

## 4. Three passes

1. **Spec.** For every criterion: **Met**, **Partial**, **Missing** or **Wrong**, with `path:line` evidence (a test name counts). Then list behaviour the diff adds that no criterion asked for (scope creep).
2. **Standards.** Each violation quotes the rule and cites where it lives (`AGENTS.md` section, rule ID, ADR).
3. **Judgement.** No written rule applies. Look for the smell baseline: Mysterious Name, Duplicated Code, Feature Envy, Data Clumps, Primitive Obsession, Repeated Switches, Shotgun Surgery, Divergent Change, Speculative Generality, Message Chains, Middle Man, Refused Bequest. Also design concerns and correctness concerns no rule covers. A documented rule overrides a smell: if the repo deliberately does it this way, it is not a finding.

## 5. Classify every finding

**Type** says what the finding is measured against:

| Type | Measured against | Must include |
| --- | --- | --- |
| **Spec** | A criterion of a linked issue (missing, partial, wrong, or unrequested behaviour) | `#N/AC-k` and the quoted criterion |
| **Standards** | A documented repo rule | Where the rule lives, and the quoted rule |
| **Judgement** | No written rule | The reasoning; for correctness, a concrete failing scenario |

**Criticality** says how much it matters:

| Level | Meaning | Typical triggers |
| --- | --- | --- |
| **Critical** | Must fix before merge | Unmet or wrong criterion; security, data loss or data-isolation break; `api.json` and Orval drift; a documented rule broken so behaviour is wrong; verification failing |
| **Warning** | Fix in this PR, or defer with a linked follow-up issue | Partial criterion; working code that breaks a layer or boundary rule; tests a criterion names are missing |
| **Suggestion** | Optional, never blocks | Naming, small duplication, local readability, low-impact rules |

Rules:

- A Judgement finding is Critical only with a concrete failing scenario (this input leads to this wrong output, or an exploit path). Otherwise its ceiling is Warning.
- Hedged findings ("could theoretically", "might", "in some cases") drop one level.
- On an **S** diff, report at most 3 Suggestions; pick the most useful.
- Never invent findings to fill a section. "No findings" is a valid result.

## 6. Self-check

Re-open every Critical and Warning at its `path:line`:

- Confirm the diff introduced or worsened it (`git blame`, or compare with `origin/develop`). Anything older goes to Follow-ups, not Findings.
- Confirm the quoted criterion or rule says what the finding claims. If not, drop the finding.
- Confirm the evidence is exact: right file, right line, quote copied, not paraphrased.

## 7. Report

Use [references/report-template.md](references/report-template.md). The verdict equals the highest criticality present:

| Highest present | Verdict |
| --- | --- |
| Critical | Blocked |
| Warning | Changes requested |
| Suggestion only | Ready with suggestions |
| None | Ready |

Print the report in chat. If the user wants it on the PR, hand over to `post-review-to-pr`.

## Done when

- Every criterion of every linked issue has a row with a status and evidence (or the report says why Spec was not checked).
- Every finding has exactly one type and one criticality, and every Critical or Warning survived the self-check.
- Pre-existing problems are under Follow-ups, offered to the user as `needs-triage` issues through `write-issue`.
