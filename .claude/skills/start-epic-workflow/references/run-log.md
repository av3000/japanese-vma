# Agent run log

One Markdown file per epic run, so the maintainer can read afterwards what each agent did, with which skill, on which branch, and what failed. It is a log, not a report: short rows, written as things happen.

## Where

`<main checkout>/.agent-runs/epic-<epic-issue>.md`. The folder is gitignored. Every worktree and session writes to the same absolute path, so the log never conflicts in git and never reaches `develop`. Find the main checkout from any worktree:

```bash
LOG="$(dirname "$(git rev-parse --path-format=absolute --git-common-dir)")/.agent-runs/epic-<epic-issue>.md"
mkdir -p "$(dirname "$LOG")"
```

## Who writes

Every agent appends its own rows: the epic agent, each story session and each review session. Sessions run one at a time, so appends do not collide. Append; never rewrite or reorder what is there. Each agent records what only it saw: an environment trap, a retry, a blocked permission, a check it could not run.

## Shape

The epic agent creates the file with a header the first time:

```markdown
# Epic #659 TRANSLATIONS · branch feature/issue-659-translations

Started 2026-11-06 by the epic agent (start-epic-workflow).
```

Each story gets a section, opened by the epic agent when it starts the story session:

```markdown
## TRANSLATIONS-01 · #660 · claude/translations-01-be9ac1 → PR #668

| When | Agent (model) | Skill | Action | Result | Note |
|---|---|---|---|---|---|
| 11-06 12:41 | epic (Opus 5.5) | start-epic-workflow | merge origin/develop, start story session tr-01-impl | ✅ | |
| 11-06 13:10 | tr-01-impl (Opus 5.5) | executing-plans | 44 targeted tests in lane jvma659-01 | ✅ | first run hit the Composer 300 s timeout |
| 11-08 19:40 | tr-01-impl | address-pr-review | reply and resolve 10 threads | ❌→✅ | classifier blocked the relayed approval |

**Retro:** ✅ review found 8 real bugs · ❌ Docker was down at start · missing: permission rules for unattended GitHub writes
```

- **When:** `MM-DD HH:MM`, local time.
- **Agent:** the session name, with the model the first time it appears.
- **Skill:** the skill driving the action, or `—`.
- **Action:** what was done, in a few words, naming the PR, commit or command that matters.
- **Result:** ✅ done, ❌ failed, ⚠️ done with a caveat, ❌→✅ failed then recovered.
- **Note:** only what someone analysing the run would need. Leave it empty otherwise.

One row per action that matters: start, plan written, test run, commit, push, PR opened, review posted, fixes pushed, merge, issue closed, any stop. Not one row per tool call.

**Retro:** one to three bullets per story. The story session writes it after the review is addressed; the epic agent adds to it after the merge. What went well (✅), what went wrong (❌), what was missing.

The epic agent ends the file with a `## Run retro` section: stories delivered, stops and why, recurring problems, and what to change in the skills.

## At the end

The epic agent copies a condensed version (the ❌ rows and every retro) into the epic PR body under `## Agent run log`, so the record outlives the local file.
