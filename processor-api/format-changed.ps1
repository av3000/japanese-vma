param(
    [switch]$Test,

    [string]$Base = 'origin/develop'
)

# Formats only the PHP files this branch changed, mirroring what
# .github/workflows/backend-ci.yml checks.
#
# Pint's own --dirty flag cannot be used inside the containers: the repository
# .git directory lives one level above processor-api/, which is the only path
# bind-mounted into them, so Pint silently reports "0 files". Git therefore runs
# on the host here and the resulting file list is passed into the container.

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$repositoryRoot = (& git rev-parse --show-toplevel).Trim()

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

$pintArgs = @('compose', 'exec', '-T', 'laravel-app', 'vendor/bin/pint')
if ($Test) {
    $pintArgs += '--test'
}

& docker @pintArgs @files
exit $LASTEXITCODE
