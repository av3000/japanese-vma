$ErrorActionPreference = 'Stop'

$validatorPath = Join-Path $PSScriptRoot 'validate-docs.ps1'
$testRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('japanese-vma-doc-tests-' + [guid]::NewGuid().ToString('N'))
$script:passed = 0
$script:failed = 0

$requiredAiFiles = @(
    'architecture-description.md',
    'product-requirements.md',
    'current-target-state.md',
    'evidence-manifest.md',
    'validation-report.md',
    'recaps/project-dashboard.md',
    'recaps/technical-handoff.md'
)

$requiredArchitectureFiles = @(
    'system-context.md',
    'application-boundaries.md',
    'deployment-and-runtime.md',
    'data-and-integrations.md'
)

$featureDirectories = @(
    'articles',
    'catalogues-and-saved-lists',
    'japanese-study-material',
    'community-and-engagement'
)

$featureFiles = @(
    'abstract.md',
    'vocabulary.md',
    'behavior.md',
    'mutations.md',
    'user-stories.md',
    'current-to-target.md'
)

function New-ValidDocument {
    param([string]$Path)

    $content = @'
# Fixture Document

> **Status:** Verified current
> **Last reviewed:** 2026-08-18
> **Evidence baseline:** Test fixture
> **Audience:** Validator tests

Fixture content.
'@

    $parent = Split-Path -Parent $Path
    New-Item -ItemType Directory -Path $parent -Force | Out-Null
    Set-Content -LiteralPath $Path -Value $content -Encoding utf8NoBOM
}

function New-ValidFixture {
    param([string]$Name)

    $repositoryRoot = Join-Path $testRoot $Name
    $docsRoot = Join-Path $repositoryRoot 'docs'
    New-Item -ItemType Directory -Path (Join-Path $repositoryRoot 'client/src') -Force | Out-Null
    Set-Content -LiteralPath (Join-Path $repositoryRoot 'AGENTS.md') -Value '# Fixture rules' -Encoding utf8NoBOM
    Set-Content -LiteralPath (Join-Path $repositoryRoot 'CONTEXT.md') -Value '# Fixture context' -Encoding utf8NoBOM
    Set-Content -LiteralPath (Join-Path $repositoryRoot 'README.md') -Value '# Fixture readme' -Encoding utf8NoBOM
    Set-Content -LiteralPath (Join-Path $repositoryRoot '.gitlab-ci.yml') -Value 'stages: []' -Encoding utf8NoBOM
    Set-Content -LiteralPath (Join-Path $repositoryRoot 'client/src/existing.ts') -Value 'export {}' -Encoding utf8NoBOM

    foreach ($relativePath in $requiredAiFiles) {
        New-ValidDocument -Path (Join-Path $docsRoot (Join-Path 'ai' $relativePath))
    }

    foreach ($relativePath in $requiredArchitectureFiles) {
        New-ValidDocument -Path (Join-Path $docsRoot (Join-Path 'architecture' $relativePath))
    }

    foreach ($featureDirectory in $featureDirectories) {
        foreach ($featureFile in $featureFiles) {
            New-ValidDocument -Path (Join-Path $docsRoot (Join-Path "feature-artifacts/$featureDirectory" $featureFile))
        }
    }

    return [pscustomobject]@{
        RepositoryRoot = $repositoryRoot
        DocsRoot = $docsRoot
    }
}

function Invoke-Validator {
    param(
        [string]$RepositoryRoot,
        [string]$DocsRoot
    )

    $output = & pwsh -NoProfile -File $validatorPath -RepositoryRoot $RepositoryRoot -DocsRoot $DocsRoot 2>&1
    $exitCode = $LASTEXITCODE

    return [pscustomobject]@{
        ExitCode = $exitCode
        Output = ($output | Out-String)
    }
}

function Assert-Equal {
    param(
        [object]$Actual,
        [object]$Expected,
        [string]$Message
    )

    if ($Actual -ne $Expected) {
        throw "$Message Expected '$Expected' but received '$Actual'."
    }
}

function Assert-Contains {
    param(
        [string]$Actual,
        [string]$Expected,
        [string]$Message
    )

    if (-not $Actual.Contains($Expected, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "$Message Expected output to contain '$Expected'. Output: $Actual"
    }
}

function Invoke-Test {
    param(
        [string]$Name,
        [scriptblock]$Arrange,
        [scriptblock]$Assert
    )

    try {
        $fixture = New-ValidFixture -Name ([guid]::NewGuid().ToString('N'))
        & $Arrange $fixture
        $result = Invoke-Validator -RepositoryRoot $fixture.RepositoryRoot -DocsRoot $fixture.DocsRoot
        & $Assert $result
        $script:passed++
        Write-Output "PASS: $Name"
    }
    catch {
        $script:failed++
        Write-Output "FAIL: $Name"
        Write-Output "  $($_.Exception.Message)"
    }
}

try {
    if (-not (Test-Path -LiteralPath $validatorPath -PathType Leaf)) {
        throw "Validator script not found: $validatorPath"
    }

    New-Item -ItemType Directory -Path $testRoot -Force | Out-Null

    Invoke-Test -Name 'complete baseline passes' -Arrange {} -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 0 -Message 'Complete fixture should pass.'
    }

    Invoke-Test -Name 'missing cross-project file fails' -Arrange {
        param($fixture)
        Remove-Item -LiteralPath (Join-Path $fixture.DocsRoot 'ai/product-requirements.md')
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Missing required file should fail.'
        Assert-Contains -Actual $result.Output -Expected 'ai/product-requirements.md' -Message 'Missing file should be named.'
    }

    Invoke-Test -Name 'incomplete feature packet fails' -Arrange {
        param($fixture)
        Remove-Item -LiteralPath (Join-Path $fixture.DocsRoot 'feature-artifacts/articles/mutations.md')
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Incomplete packet should fail.'
        Assert-Contains -Actual $result.Output -Expected 'feature-artifacts/articles/mutations.md' -Message 'Missing packet file should be named.'
    }

    Invoke-Test -Name 'broken relative Markdown link fails' -Arrange {
        param($fixture)
        Add-Content -LiteralPath (Join-Path $fixture.DocsRoot 'ai/architecture-description.md') -Value '[Missing](./missing.md)'
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Broken link should fail.'
        Assert-Contains -Actual $result.Output -Expected './missing.md' -Message 'Broken link target should be named.'
    }

    Invoke-Test -Name 'absolute local path fails' -Arrange {
        param($fixture)
        Add-Content -LiteralPath (Join-Path $fixture.DocsRoot 'ai/architecture-description.md') -Value 'Local path: C:\Users\example\secret.txt'
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Absolute path should fail.'
        Assert-Contains -Actual $result.Output -Expected 'absolute local path' -Message 'Absolute-path error should be actionable.'
    }

    Invoke-Test -Name 'unfinished-work marker fails' -Arrange {
        param($fixture)
        Add-Content -LiteralPath (Join-Path $fixture.DocsRoot 'ai/architecture-description.md') -Value 'TBD: incomplete content'
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Unfinished content should fail.'
        Assert-Contains -Actual $result.Output -Expected 'unfinished-work marker' -Message 'Marker error should be actionable.'
    }

    Invoke-Test -Name 'missing metadata fails' -Arrange {
        param($fixture)
        $path = Join-Path $fixture.DocsRoot 'architecture/system-context.md'
        $content = Get-Content -Raw -LiteralPath $path
        Set-Content -LiteralPath $path -Value ($content -replace '(?m)^> \*\*Audience:\*\*.*\r?\n?', '') -Encoding utf8NoBOM
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Missing metadata should fail.'
        Assert-Contains -Actual $result.Output -Expected 'Audience' -Message 'Missing metadata field should be named.'
    }

    Invoke-Test -Name 'missing repository path fails' -Arrange {
        param($fixture)
        Add-Content -LiteralPath (Join-Path $fixture.DocsRoot 'ai/evidence-manifest.md') -Value 'Evidence: `client/src/missing.ts`'
    } -Assert {
        param($result)
        Assert-Equal -Actual $result.ExitCode -Expected 1 -Message 'Missing repository path should fail.'
        Assert-Contains -Actual $result.Output -Expected 'client/src/missing.ts' -Message 'Missing repository path should be named.'
    }

    Write-Output "Tests: $($script:passed) passed, $($script:failed) failed"
    if ($script:failed -gt 0) {
        exit 1
    }
}
finally {
    if (Test-Path -LiteralPath $testRoot) {
        Remove-Item -LiteralPath $testRoot -Recurse -Force
    }
}
