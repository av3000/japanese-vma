#!/usr/bin/env bash
# Host PR review screenshots on a throwaway branch, so the PR body can show them.
#
# GitHub only renders images that live at a URL, and `gh` cannot upload attachments, so the
# PNGs go to a temporary branch `screenshots/<feature-branch>` that holds nothing but the current
# set. It is not storage: each run replaces the branch with one fresh commit (no history), and
# `--delete` removes it once the PR is merged or closed.
#
# Usage:
#   publish-screenshots.sh <png-dir> <feature-branch>   publish (or replace) the current set
#   publish-screenshots.sh --delete <feature-branch>    remove the branch after merge or close
#
# DRY_RUN=1 builds the commit locally and skips the push. The working tree is never touched.
set -euo pipefail

usage="usage: publish-screenshots.sh <png-dir> <feature-branch> | --delete <feature-branch>"

if [ "${1:-}" = "--delete" ]; then
	feature="${2:?$usage}"
	git push -q origin --delete "screenshots/${feature}"
	echo "deleted: screenshots/${feature}"
	exit 0
fi

dir="${1:?$usage}"
feature="${2:?$usage}"
target="screenshots/${feature}"

shopt -s nullglob
files=("$dir"/*.png)
if [ "${#files[@]}" -eq 0 ]; then
	echo "No .png files in $dir" >&2
	exit 1
fi

repo="$(git remote get-url origin | sed -E 's#^(git@github.com:|https://github.com/)##; s#\.git$##')"

tree="$(
	for f in "${files[@]}"; do
		printf '100644 blob %s\t%s\n' "$(git hash-object -w "$f")" "$(basename "$f")"
	done | git mktree
)"

# A parentless commit: the branch only ever holds the current screenshots.
commit="$(git commit-tree "$tree" -m "Review screenshots for ${feature} (temporary)")"

if [ "${DRY_RUN:-}" = "1" ]; then
	echo "dry run: not pushed"
else
	# Force is safe here: this branch belongs to the screenshots alone and is replaced on purpose.
	git push -q origin "+${commit}:refs/heads/${target}"
fi

echo "branch: ${target}"
echo "url prefix: https://raw.githubusercontent.com/${repo}/${commit}/"
