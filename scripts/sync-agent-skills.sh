#!/usr/bin/env bash
# Mirror the agent skills for Codex.
#
# .claude/skills/ is canonical (Claude Code reads it). .codex/skills/ is a
# generated copy for Codex. Edit skills under .claude/skills/ only, then run:
#
#   bash scripts/sync-agent-skills.sh
#
# Every skill directory in .claude/skills/ replaces the matching one in
# .codex/skills/. Codex skills with no .claude counterpart are removed, unless
# they are listed in CODEX_ONLY below. CI runs this script and fails when the
# result differs from what is committed.
set -euo pipefail
shopt -s nullglob

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
src="$root/.claude/skills"
dst="$root/.codex/skills"

# Skills that still live only in .codex/skills/ until epic #502 moves them.
CODEX_ONLY=(
  backend-architecture-boundaries
  react-best-practices
  saved-list-to-catalogue-v1-migration
  scramble-orval-contract-debugging
)

is_codex_only() {
  local name=$1 kept
  for kept in "${CODEX_ONLY[@]}"; do
    [[ $kept == "$name" ]] && return 0
  done
  return 1
}

mkdir -p "$dst"

for dir in "$dst"/*/; do
  name=$(basename "$dir")
  if [[ ! -d $src/$name ]] && ! is_codex_only "$name"; then
    rm -rf "$dir"
    echo "removed  $name (no longer in .claude/skills)"
  fi
done

for dir in "$src"/*/; do
  name=$(basename "$dir")
  rm -rf "${dst:?}/$name"
  cp -R "$dir" "$dst/$name"
  echo "mirrored $name"
done
