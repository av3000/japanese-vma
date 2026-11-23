#!/usr/bin/env sh

# Formats only the PHP files this branch changed, mirroring what
# .github/workflows/backend-ci.yml checks.
#
# Pint's own --dirty flag cannot be used inside the containers: the repository
# .git directory lives one level above processor-api/, which is the only path
# bind-mounted into them, so Pint silently reports "0 files". Git therefore runs
# on the host here and the resulting file list is passed into the container.
#
# Usage:
#   ./format-changed.sh              # fix changed files
#   ./format-changed.sh --test       # check only, non-zero exit on drift
#   BASE_REF=origin/master ./format-changed.sh
#   LANE=jvma520 ./format-changed.sh # run Pint in an isolated test lane
#
# Without LANE, Pint runs in the default compose file's `laravel-app`. Its
# container name is fixed, so from a git worktree that is the main checkout's
# container and Pint would format the wrong tree. The script refuses that case.

set -eu

cd "$(dirname "$0")"

repository_root="$(git rev-parse --show-toplevel)"

lane="${LANE:-}"
if [ -z "$lane" ] && [ "$(git rev-parse --path-format=absolute --git-dir)" != "$(git rev-parse --path-format=absolute --git-common-dir)" ]; then
    echo "This is a git worktree, and the default laravel-app container is bound to another checkout." >&2
    echo "Run Pint in this worktree's test lane instead: LANE=<name> ./format-changed.sh" >&2
    echo "(the lane from: docker compose -f docker-compose.test.yml -p <name> up -d --build)" >&2
    exit 1
fi

pint_test_flag=""
if [ "${1:-}" = "--test" ]; then
    pint_test_flag="--test"
fi

base_ref="${BASE_REF:-origin/develop}"

if ! git rev-parse --verify --quiet "${base_ref}^{commit}" >/dev/null 2>&1; then
    echo "Base ref '${base_ref}' not found, falling back to 'develop'."
    base_ref="develop"

    if ! git rev-parse --verify --quiet "${base_ref}^{commit}" >/dev/null 2>&1; then
        echo "Neither the requested base ref nor 'develop' resolves to a commit." >&2
        exit 1
    fi
fi

merge_base="$(git merge-base "$base_ref" HEAD)"

changed_files="$(
    {
        git -C "$repository_root" diff --name-only --diff-filter=ACMR "$merge_base" -- '*.php'
        git -C "$repository_root" ls-files --others --exclude-standard -- '*.php'
    } | sed -n 's#^processor-api/##p' | sort -u
)"

if [ -z "$changed_files" ]; then
    echo "No changed PHP files under processor-api/ compared to ${base_ref}."
    exit 0
fi

file_count="$(printf '%s\n' "$changed_files" | wc -l | tr -d ' ')"
echo "Running Pint on ${file_count} changed PHP file(s) against ${base_ref}."

# Build the argument list explicitly rather than piping through xargs, so Pint's
# own exit code reaches the caller instead of xargs' 123.
set --
while IFS= read -r changed_file; do
    if [ -n "$changed_file" ]; then
        set -- "$@" "$changed_file"
    fi
done <<EOF
$changed_files
EOF

if [ -n "$pint_test_flag" ]; then
    set -- "$pint_test_flag" "$@"
fi

if [ -n "$lane" ]; then
    docker compose -f docker-compose.test.yml -p "$lane" exec -T test-runner vendor/bin/pint "$@"
else
    docker compose exec -T laravel-app vendor/bin/pint "$@"
fi
