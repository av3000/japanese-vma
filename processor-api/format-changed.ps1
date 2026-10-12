param(
    [switch]$Test,

    [string]$Base = 'origin/develop',

    # Project name of an isolated test lane (docker-compose.test.yml -p <name>).
    [string]$Lane = ''
)

# Formats only the PHP files this branch changed, mirroring what
# .github/workflows/backend-ci.yml checks.
#
# Pint's own --dirty flag cannot be used inside the containers: the repository
# .git directory lives one level above processor-api/, which is the only path
# bind-mounted into them, so Pint silently reports "0 files". Git therefore runs
# on the host here and the resulting file list is passed into the container.
#
# Without -Lane, Pint runs in the default compose file's `laravel-app`. Its
# container name is fixed, so from a git worktree that is the main checkout's
# container and Pint would format the wrong tree. The script refuses that case.

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$repositoryRoot = (& git rev-parse --show-toplevel).Trim()

$gitDir = (& git rev-parse --path-format=absolute --git-dir).Trim()
$gitCommonDir = (& git rev-parse --path-format=absolute --git-common-dir).Trim()
if (-not $Lane -and $gitDir -ne $gitCommonDir) {
    Write-Host 'This is a git worktree, and the default laravel-app container is bound to another checkout.'
    Write-Host 'Run Pint in this worktree''s test lane instead: ./format-changed.ps1 -Lane <name>'
    Write-Host '(the lane from: docker compose -f docker-compose.test.yml -p <name> up -d --build)'
    exit 1
}

function Test-GitRef([string]$ref) {
    & git rev-parse --verify --quiet "$ref^{commit}" > $null 2>&1
    return $LASTEXITCODE -eq 0
}

$baseRef = $Base
if (-not (Test-GitRef $baseRef)) {
    Write-Host "Base ref '$Base' not found, falling back to 'develop'."
    $baseRef = 'develop'

    if (-not (Test-GitRef $baseRef)) {
        throw "Neither '$Base' nor 'develop' resolves to a commit."
    }
}

$mergeBase = (& git merge-base $baseRef HEAD).Trim()

$candidates = @()
$candidates += & git -C $repositoryRoot diff --name-only --diff-filter=ACMR $mergeBase -- '*.php'
$candidates += & git -C $repositoryRoot ls-files --others --exclude-standard -- '*.php'

$files = $candidates |
    Where-Object { $_ -like 'processor-api/*' } |
    ForEach-Object { $_ -replace '^processor-api/', '' } |
    Sort-Object -Unique

if (-not $files) {
    Write-Host "No changed PHP files under processor-api/ compared to $baseRef."
    exit 0
}

$mode = if ($Test) { 'Checking' } else { 'Formatting' }
Write-Host "$mode $($files.Count) changed PHP file(s) against $baseRef."

$pintArgs = if ($Lane) {
    @('compose', '-f', 'docker-compose.test.yml', '-p', $Lane, 'exec', '-T', 'test-runner', 'vendor/bin/pint')
} else {
    @('compose', 'exec', '-T', 'laravel-app', 'vendor/bin/pint')
}
if ($Test) {
    $pintArgs += '--test'
}

& docker @pintArgs @files
exit $LASTEXITCODE
