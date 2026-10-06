---
name: create-pull-request
description: Create or prepare a GitHub pull request for the current japanese-vma feature branch into develop, with a reviewer-focused title and body, Storybook stories for every new or changed UI state, and desktop, tablet and mobile screenshots whenever the change is visible in the UI. Use this whenever the user asks to open, create, raise, prepare or finish a PR, to "ship" or "deliver" a branch or phase, or to add or refresh screenshots on a PR, even if they don't say "pull request". Do not use for merging, reviewing someone else's PR, or changing develop itself.
---

# Create Pull Request

Open one focused PR from the current feature branch into `develop`, written so a reviewer can judge it from the PR page alone: what changed, what was verified, and, for anything visible, what it looks like on desktop, tablet and mobile. The only intended remote changes are pushing the feature branch and creating or editing the PR; the screenshots upload with the PR as GitHub attachments.

## 1. Inspect before acting

1. Read the root `AGENTS.md` and the scoped `AGENTS.md` for every area the diff touches.
2. `git fetch origin`, then inspect the branch, `git status`, `git log origin/develop..HEAD` and `git diff --stat origin/develop...HEAD`.
3. Stop if the branch is `develop`, `master` or detached, or has no commits beyond `origin/develop`.
4. Look for an open PR from this branch and update it instead of creating a duplicate.
5. Leave uncommitted files out. If they look necessary for the PR to be complete, stop and tell the user; otherwise mention that they were excluded.
6. If `develop` moved since the branch point, check whether any file changed on both sides (`comm -12` of the two `--name-only` diffs). No overlap: push as is, CI tests the merge result. Overlap: tell the user before merging anything.

## 2. Remote safety

- `gh` is not on PATH on this machine: call `C:\Program Files\GitHub CLI\gh.exe` (Bash: `'/c/Program Files/GitHub CLI/gh.exe'`). The GitHub MCP server is unreliable here; use `gh`.
- Never commit to, merge into, rebase, reset or force-push `develop`.
- Push only the current branch, with an explicit refspec: `git push -u origin <branch>:<branch>`. Check `git branch --show-current` right before every commit and push; a changed working directory never changes which branch the task is on.
- After pushing, confirm the remote branch points at local `HEAD` and that `origin/develop` did not move because of you.

## 3. Stories for every new or changed UI state

Stories are where reviewers, tests and screenshots all look, so they have to match the branch before the PR opens.

- **New or unique case** (a new component, a new state such as an error, empty, locked or long-text case, or a new variant): add a story for it, with a `play` function when the state needs interaction to reach.
- **Modified feature**: find every story that renders the changed component or page (`grep -rl "<ComponentName>" client/src --include=*.stories.tsx`) and update its args, fixtures and `play` assertions, so no story still shows or asserts the old behaviour. Delete stories for states that no longer exist.
- Signed-in UI goes through a story that supplies `AuthContext`, as `components/features/Header/Header.stories.tsx` does.
- Commit these story changes with the slice they belong to, before creating the PR. `npm run build-storybook` and `npx vitest run` must pass with them.

## 4. Screenshots, when anything visible changed

A reviewer should not have to check out the branch to see the result. If the diff changes anything a user can see (components, pages, CSS, copy, layout, states), the PR body gets a `## Screenshots` section with each materially changed state at three widths:

| Size | Width |
|---|---|
| Desktop | 1400px |
| Tablet | 768px |
| Mobile | 360px |

These match the repo's literal breakpoints (768, 1024, 1320), so the tablet shot shows the stacked layout and the desktop shot the widest one. Capture the states a reviewer would otherwise have to imagine: the normal state, plus each error, empty, loading, locked or long-text state the change touches. Skip the section only when nothing visible changed (backend-only, tests, tooling), and say so in one line under Verification.

The images are uploaded with `gh pr create|edit --attach` to GitHub's own attachment store, so they stay visible after the merge and need no cleanup.

How to capture and upload them is in `references/screenshots.md` in this skill's folder; read it when this section applies. In short: shoot the stories from section 3 at the three widths, look at every image, reference each PNG as a Markdown image in a Desktop / Tablet / Mobile table in the body file, and upload the PNGs with `--attach` in the same command that creates or edits the PR.

If a later commit changes the visuals (for example review feedback), re-shoot and edit the body again with the new attachments (the re-shoot steps are in the reference), so the body never shows superseded UI.

## 5. Write for reviewers

Title: the combined outcome, not one commit. Phase PRs follow `<PHASE-ID>: <outcome> (#<phase issue>)`.

Body, in this order, omitting empty sections:

```markdown
<one or two sentences: what this delivers and which issue or phase it belongs to>

## Commits → issues        (multi-slice PRs: commit SHA, issue, one-line what)

## Summary                 (single-slice PRs: grouped bullets instead of the table)

## Behaviour changes       (anything a user or another caller will notice)

## Screenshots             (see section 4)

## Verification            (each command actually run, with its real result)

## Follow-ups              (filed issues, known gaps)

Refs #<issue>, #<issue>
```

- Group by behaviour or area, not commit order. Explain decisions and boundaries; skip filename inventories and diff stats.
- **Verification** lists only what ran in this work, with the real result line (for Vitest the `Test Files N passed (M)` line, compared with the baseline). If a relevant check did not run, say which and why. Usual client checks from `client/`: `npm run lint`, `npm run typecheck`, `npx vitest run`, `NODE_ENV=production npm run build`, `npm run build-storybook`, `npm run style:audit -- --check`. Backend: the Docker test lane per `processor-api/AGENTS.md`.
- **Issue references:** use `Refs #N`. PRs to `develop` never auto-close issues (`Closes` only fires on the default branch), and the user closes issues by hand after merge. List the issues to close in the final handoff.
- **No attribution:** no `Co-Authored-By` trailer and no "Generated with …" footer, in commits or the PR body.
- Name any open PR or worktree whose files this PR deliberately did not touch, so reviewers know the boundary.

## 6. Create and verify

1. Write the body to a file and pass it with `--body-file`. Do this from Bash: PowerShell captures have flattened bodies into one line.
2. `gh pr create --repo av3000/japanese-vma --base develop --head <branch> --title "…" --body-file <file>`, adding one `--attach <png>` per screenshot when the body has a `## Screenshots` section (see `references/screenshots.md` for the working directory and path rule).
3. Verify URL, base, head, commit count and mergeability (`gh pr view <n> --json baseRefName,headRefName,commits,mergeable`). If the Claude desktop app's `ccd_pr` tools are available, use `get_status` instead of polling checks.
4. CI on this repo runs only on PRs to `develop`, so the PR is the first remote CI run; say so, and expect a fix-up pass.
5. Hand off: the PR link, the screenshot section (or why there is none), anything not verified, and the issues to close by hand after merge.

To edit an existing PR body later, fetch it with `gh pr view <n> --json body --jq .body > body.md`, edit the file, and send it back with `gh pr edit <n> --body-file body.md`.
