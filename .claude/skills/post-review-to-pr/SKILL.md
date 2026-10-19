---
name: post-review-to-pr
description: Post a review produced by review-branch to its GitHub pull request as one pending review, with an inline comment for every finding on a changed line and the summary in the review body. Submits only after the user confirms, and detects re-runs so the same review is never posted twice. Use when the user asks to post, publish, attach or leave a review on a PR. Producing the review is review-branch; answering comments is address-pr-review.
---

# Post review to PR

Turn a `review-branch` report into a GitHub review a reader can follow on the PR page. Nothing becomes visible to others until the user confirms.

`gh` lives at `/c/Program Files/GitHub CLI/gh.exe`; run it from Bash. Write every JSON payload to a file with the Write tool and pass it with `--input`, so quoting never breaks. Repo: `av3000/japanese-vma`.

## 1. Input

Accept only a report in the `review-branch` format (`.claude/skills/review-branch/references/report-template.md`): from this session, or from a file the user names. Refuse anything else and offer to run `review-branch` first.

## 2. Metadata

```bash
gh pr view <n> --repo av3000/japanese-vma --json number,url,headRefOid,author
gh api user --jq .login
gh api repos/av3000/japanese-vma/pulls/<n>/reviews --paginate
```

- If the report was made against a different commit than `headRefOid`, say so and offer to re-run `review-branch` first.
- If the current user already has a **PENDING** review on this PR, GitHub allows only one: ask whether to submit it, discard it, or stop.

## 3. Re-runs

The review body ends with `<!-- review-branch:<headRefOid> -->`.

- A submitted review with the **same** marker exists: stop and ask. Posting again would duplicate it.
- A review with an **older** marker exists: title this one "Re-review". Skip any finding whose path and first line of text already appear in an earlier review's comments, and list them as "still open" in the body instead.

## 4. Build the payload

```json
{
  "commit_id": "<headRefOid>",
  "body": "<verdict, scope, risk, AC table, findings table, follow-ups>\n\n<!-- review-branch:<headRefOid> -->",
  "comments": [
    { "path": "client/src/x.ts", "line": 42, "side": "RIGHT", "body": "**F3 [Standards · Warning]** client/AGENTS.md §3 \"…\"\n\nWhat is wrong. Why. Suggested fix." }
  ]
}
```

- One comment per finding that sits on a line the PR changed. For a range, add `"start_line"` and `"start_side": "RIGHT"`.
- Findings without a changed line (missing criteria, whole-file concerns) stay in the body only.
- The body keeps the full findings table, so the summary reads on its own.
- Leave out `event`. That creates the review as **PENDING**: visible only to you.
- No attribution footer.

## 5. Create the pending review

```bash
gh api repos/av3000/japanese-vma/pulls/<n>/reviews --method POST --input review.json
```

On `422` "line could not be resolved" (the line is outside the diff), move that comment into the body as a bullet with its `path:line`, and retry once.

## 6. Confirm

Show the user:

- the counts by type and criticality, and how many findings went inline vs body-only;
- the PR link, where the pending review is visible to them alone.

Ask: **submit** or **discard**?

## 7. Submit or discard

- **Submit:** `gh api repos/av3000/japanese-vma/pulls/<n>/reviews/<id>/events --method POST -f event=<EVENT>`
  - `EVENT` is `COMMENT` by default.
  - Use `REQUEST_CHANGES` only when the verdict is **Blocked** and the current user is **not** the PR author. GitHub rejects approve and request-changes on your own PR.
  - Never `APPROVE`.
- **Discard:** `gh api repos/av3000/japanese-vma/pulls/<n>/reviews/<id> --method DELETE`. This deletes only the pending review this run created.

## 8. Follow-ups

For each Follow-up in the report, offer to file it through `write-issue` as `needs-triage`. Each issue needs its own confirmation.

## Done when

- The review is submitted (or discarded) with the user's explicit choice, and the review URL is reported.
- Every finding is either an inline comment or a bullet in the body. None is lost to a 422.
- The body carries the `review-branch:<sha>` marker.
