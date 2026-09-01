---
name: create-pull-request
description: Create a GitHub pull request for the current japanese-vma feature branch, targeting develop with a short reviewer-focused title and description. Use when the user asks to create, open, or prepare a PR; do not use for merging or changing develop itself.
---

# Create Pull Request

Create a focused PR from the current feature branch into `develop`. Preserve the repository and the remote base branch; the only intended mutations are pushing the current feature branch when needed and creating its PR.

## Inspect Before Acting

1. Read the root `AGENTS.md` and the scoped instructions for changed areas.
2. Inspect the current branch, worktree status, commits, and diff against the latest `origin/develop`.
3. Stop if the current branch is `develop`, detached, or has no committed changes relative to `origin/develop`.
4. Check for an existing open PR from the same branch and reuse it instead of creating a duplicate.
5. Do not include uncommitted files. If they appear related to the PR and would make it incomplete, pause and tell the user; otherwise leave them untouched and mention that they are excluded.

## Remote Safety

- Fetch `origin/develop` before evaluating the PR.
- Never commit, merge, rebase, reset, force-push, or update `develop` as part of this skill.
- Never use an implicit `git push`. Push only the checked-out feature branch with an explicit refspec equivalent to `HEAD:refs/heads/<current-branch>`.
- Verify after pushing that the remote feature branch points to local `HEAD` and that the remote `develop` SHA did not change.
- Stop on unexpected divergence or remote configuration instead of repairing history automatically.

## Write for Reviewers

Choose a short title that describes the combined outcome, not an individual commit.

Use this compact body unless a repository PR template requires additional information:

```markdown
## Summary

- <cohesive reviewer-relevant change>
- <cohesive reviewer-relevant change>

## Testing

- <checks actually run and their result>

Related to #<issue>.
```

Body rules:

- Use as many `Summary` bullets as needed to cover distinct reviewer-relevant changes, grouped by behavior or feature area rather than commit chronology. Two to seven is a useful range, not a quota or hard limit.
- Explain what changed and any important boundary or behavior decision; avoid exhaustive filenames and implementation trivia.
- Report only validation observed in the current work. If relevant checks were not run, say so briefly and give the reason.
- Include an issue reference only when it can be established from the branch, commits, or user context. Use `Closes #...` only when the PR fully resolves that issue; otherwise use `Related to #...`.
- Omit empty headings, generic statements, diff statistics, and generated filler.
- Add a short note only when reviewers need to know about a real limitation, follow-up, migration concern, or excluded work.

## Create and Verify

Create the PR with head set explicitly to the current feature branch and base set explicitly to `develop`. Then verify its URL, title, base, head, commit count, and mergeability state. Return the PR link plus any meaningful limitation in a compact summary.
