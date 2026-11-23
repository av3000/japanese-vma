# Generated: do not edit

This directory is a copy of `.claude/skills/` for Codex. Claude Code reads `.claude/skills/`; Codex reads this one.

To change a skill:

1. Edit it under `.claude/skills/<name>/`.
2. Regenerate this directory with `bash scripts/sync-agent-skills.sh`.
3. Commit both directories together.

CI runs the same script on every PR that touches skills and fails if this directory differs from what the script produces. It also lints every skill with `node scripts/lint-agent-skills.mjs`.

Skills listed in `CODEX_ONLY` in the script still live only here. They are left alone until epic #502 moves them into `.claude/skills/`.
