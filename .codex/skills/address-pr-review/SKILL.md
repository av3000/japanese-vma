---
name: address-pr-review
description: Work through the review comments on a japanese-vma pull request. Reads every thread first, sorts each into Fix, Clarify or Decline with a reason, shows that table before touching code, makes all fixes in one commit, replies to every thread and resolves the ones that are done. Use when the user says "address the review", "look at the comments", "handle PR feedback", "answer the review" or "check the PR threads". Writing a review is review-branch; posting one is post-review-to-pr.
---

# Address PR review

Assess each review comment on its merits, fix what genuinely matters, and close the loop on every thread. The default failure is agreeing to please: "fixing" something you correctly judged unnecessary, just because someone raised it. A reasoned Decline is a valid answer.

`gh` lives at `/c/Program Files/GitHub CLI/gh.exe`; run it from Bash. Repo: `av3000/japanese-vma`. Check `git branch --show-current` matches the PR's head branch before changing anything.

## 1. Read everything first

Do not start fixing after the first comment. Fetch it all:

```bash
gh api graphql -F owner=av3000 -F name=japanese-vma -F n=<pr> -f query='
query($owner:String!, $name:String!, $n:Int!) {
  repository(owner:$owner, name:$name) {
    pullRequest(number:$n) {
      headRefName author { login }
      reviewThreads(first:100) { nodes {
        id isResolved isOutdated path line
        comments(first:50) { nodes { author { login } body url } }
      } }
      reviews(first:50) { nodes { author { login } state body } }
      comments(first:100) { nodes { author { login } body url } }
    }
  }
}'
```

Skip resolved threads, CI bot noise, and reviews with an empty body. Also read the linked issues (`Refs #N` in the PR body) so you can judge Spec comments against the acceptance criteria.

## 2. Sort every thread

| Class | When | Action |
| --- | --- | --- |
| **Fix** | Correctness bug, security issue, unmet acceptance criterion, broken documented rule, real breakage | Change the code |
| **Clarify** | The code is right but non-obvious, or the reviewer lacked context | Reply; add a code comment or doc if that would have prevented the question |
| **Decline** | Style preference, theoretical risk without a failing scenario, hypothetical future need | Reply with the reason |

- Comments labelled `[Type · Criticality]` (from `review-branch` / `post-review-to-pr`): **Critical** defaults to Fix. If its quoted evidence does not hold, reply with the counter-evidence (`path:line`, the actual rule text) and let the user decide. **Suggestion** is a fair candidate for Decline.
- A request outside this PR's issues is a Decline that links a new `needs-triage` issue, created through `write-issue`.
- Outdated threads (`isOutdated`): check whether a later commit already addressed them before classifying.

## 3. Show the table before acting

| # | Thread | `path:line` | Class | Reason | Drafted reply |
| --- | --- | --- | --- | --- | --- |
| 1 | "This null check…" | `client/src/x.ts:42` | Fix | Genuine crash when … | Fixed in `<sha>`: … |
| 2 | "Consider using…" | `client/src/y.ts:18` | Decline | Style preference; current form matches `client/AGENTS.md` §4 | … |

The user may override any row. Change nothing until the user confirms the table.

## 4. Fix, push, reply, resolve

1. **Fix** every Fix row in **one commit**: `<epic or Stable ID>: address the review on #<pr>`, body `Refs #N` for the affected issues, no attribution. Run the checks the touched area needs.
2. **Push** with an explicit refspec: `git push origin <branch>:<branch>`.
3. **Reply** to every thread, only after the push, so "fixed" is never claimed before the change exists. Fix replies name the commit SHA.

   ```bash
   gh api graphql -F id=<threadId> -F body=@reply.md -f query='
   mutation($id:ID!, $body:String!) {
     addPullRequestReviewThreadReply(input:{pullRequestReviewThreadId:$id, body:$body}) { comment { url } }
   }'
   ```

   Top-level PR comments and review bodies have no thread: answer them in one `gh pr comment <pr> --body-file reply.md`.
4. **Resolve:**
   - Fix threads, once replied.
   - Clarify threads that need no further answer.
   - Decline threads from your own `post-review-to-pr` review.
   - **Leave Decline threads from another person unresolved**, so they can answer.

   ```bash
   gh api graphql -F id=<threadId> -f query='mutation($id:ID!) { resolveReviewThread(input:{threadId:$id}) { thread { isResolved } } }'
   ```

Replies are public: send them only after the user has confirmed the table and the drafted replies, in one batch.

## Stop and reconsider if you notice yourself

- reversing a correct assessment because the reviewer pushed back;
- fixing something you just classified as unnecessary;
- praising a point you don't agree with ("good catch!");
- replying "fixed" before pushing;
- folding an unrelated improvement into the fix commit.

The Fix / Clarify / Decline classes and these warning signs are adapted from other references (a private skills collection).

## Done when

- Every unresolved thread has a reply, and every Fix reply names the pushed commit.
- Fix threads are resolved; Decline threads from people stay open.
- Out-of-scope requests exist as `needs-triage` issues linked from their replies.
