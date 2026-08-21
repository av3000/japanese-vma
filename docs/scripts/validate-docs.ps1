param(
    [string]$RepositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '../..')).Path,
    [string]$DocsRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
)

$ErrorActionPreference = 'Stop'

$requiredAiFiles = @(
    'ai/architecture-description.md',
    'ai/product-requirements.md',
    'ai/current-target-state.md',
    'ai/evidence-manifest.md',
    'ai/validation-report.md',
    'ai/recaps/project-dashboard.md',
    'ai/recaps/technical-handoff.md'
)

$requiredArchitectureFiles = @(
    'architecture/system-context.md',
    'architecture/application-boundaries.md',
    'architecture/deployment-and-runtime.md',
    'architecture/data-and-integrations.md'
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

$metadataFields = @('Status', 'Last reviewed', 'Evidence baseline', 'Audience')
$knownRepositoryPrefixes = @('client/', 'processor-api/', 'docs/', '.github/', '.codex/', '.ai/')
$knownRootFiles = @('AGENTS.md', 'CONTEXT.md', 'README.md', '.gitlab-ci.yml')
$errors = [System.Collections.Generic.List[string]]::new()

function Add-ValidationError {
    param([string]$Message)

    $errors.Add($Message)
}

function Get-DisplayPath {
    param([string]$Path)

    $root = [System.IO.Path]::GetFullPath($RepositoryRoot).TrimEnd('\', '/')
    $fullPath = [System.IO.Path]::GetFullPath($Path)
    if ($fullPath.StartsWith($root, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $fullPath.Substring($root.Length).TrimStart('\', '/').Replace('\', '/')
    }

    return $fullPath.Replace('\', '/')
}

function Remove-FencedCodeBlocks {
    param([string]$Content)

    return [regex]::Replace($Content, '(?ms)^\s*(```|~~~).*?^\s*\1\s*$', '')
}

function Get-BaselineMarkdownFiles {
    $directories = @(
        (Join-Path $DocsRoot 'ai'),
        (Join-Path $DocsRoot 'architecture'),
        (Join-Path $DocsRoot 'feature-artifacts')
    )

    $files = foreach ($directory in $directories) {
        if (Test-Path -LiteralPath $directory -PathType Container) {
            Get-ChildItem -LiteralPath $directory -File -Filter '*.md' -Recurse
        }
    }

    return @($files | Sort-Object FullName -Unique)
}

function Test-RequiredFiles {
    $requiredFiles = [System.Collections.Generic.List[string]]::new()
    foreach ($path in $requiredAiFiles + $requiredArchitectureFiles) {
        $requiredFiles.Add($path)
    }

    foreach ($featureDirectory in $featureDirectories) {
        foreach ($featureFile in $featureFiles) {
            $requiredFiles.Add("feature-artifacts/$featureDirectory/$featureFile")
        }
    }

    foreach ($relativePath in $requiredFiles) {
        $fullPath = Join-Path $DocsRoot $relativePath
        if (-not (Test-Path -LiteralPath $fullPath -PathType Leaf)) {
            Add-ValidationError "Missing required file: $relativePath"
        }
    }
}

function Test-Metadata {
    param(
        [System.IO.FileInfo]$File,
        [string]$Content
    )

    foreach ($field in $metadataFields) {
        $pattern = '(?im)^>\s*\*\*' + [regex]::Escape($field) + ':\*\*\s*\S.+'
        if (-not [regex]::IsMatch($Content, $pattern)) {
            Add-ValidationError "Missing metadata '$field' in $(Get-DisplayPath $File.FullName)"
        }
    }
}

function Test-AbsoluteLocalPaths {
    param(
        [System.IO.FileInfo]$File,
        [string]$Content
    )

    if ([regex]::IsMatch($Content, '(?i)(?<![A-Za-z0-9])[A-Za-z]:[\\/][^\s`''"<>]+')) {
        Add-ValidationError "Found absolute local path in $(Get-DisplayPath $File.FullName)"
    }
}

function Test-UnfinishedWorkMarkers {
    param(
        [System.IO.FileInfo]$File,
        [string]$Content
    )

    if ([regex]::IsMatch($Content, '(?im)^\s*(?:[-*]\s*)?(?:TBD|TODO|FIXME)(?:\s*:|\s*$)')) {
        Add-ValidationError "Found unfinished-work marker in $(Get-DisplayPath $File.FullName)"
    }
}

function Test-MarkdownLinks {
    param(
        [System.IO.FileInfo]$File,
        [string]$Content
    )

    $matches = [regex]::Matches($Content, '!?(?:\[[^\]]*\])\((?<target>[^)\s]+)(?:\s+"[^"]*")?\)')
    foreach ($match in $matches) {
        $target = $match.Groups['target'].Value.Trim('<', '>')
        if ($target -match '^(?i:https?|mailto|data):' -or $target.StartsWith('#')) {
            continue
        }

        $pathPart = ($target -split '[#?]', 2)[0]
        if ([string]::IsNullOrWhiteSpace($pathPart) -or $pathPart.StartsWith('/')) {
            continue
        }

        $decodedPath = [uri]::UnescapeDataString($pathPart).Replace('/', [System.IO.Path]::DirectorySeparatorChar)
        $resolvedPath = [System.IO.Path]::GetFullPath((Join-Path $File.DirectoryName $decodedPath))
        if (-not (Test-Path -LiteralPath $resolvedPath)) {
            Add-ValidationError "Broken Markdown link in $(Get-DisplayPath $File.FullName): $target"
        }
    }
}

function Test-RepositoryPathReferences {
    param(
        [System.IO.FileInfo]$File,
        [string]$Content
    )

    $matches = [regex]::Matches($Content, '`(?<value>[^`\r\n]+)`')
    foreach ($match in $matches) {
        $value = $match.Groups['value'].Value.Trim()
        $isKnownRootFile = $knownRootFiles -contains $value
        $isKnownPrefixedPath = $false
        foreach ($prefix in $knownRepositoryPrefixes) {
            if ($value.StartsWith($prefix, [System.StringComparison]::OrdinalIgnoreCase)) {
                $isKnownPrefixedPath = $true
                break
            }
        }

        if (-not $isKnownRootFile -and -not $isKnownPrefixedPath) {
            continue
        }

        if ($value.IndexOfAny(@('*', '?', '|', '<', '>')) -ge 0) {
            continue
        }

        $normalizedPath = $value.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
        $resolvedPath = Join-Path $RepositoryRoot $normalizedPath
        if (-not (Test-Path -LiteralPath $resolvedPath)) {
            Add-ValidationError "Missing repository path referenced in $(Get-DisplayPath $File.FullName): $value"
        }
    }
}

if (-not (Test-Path -LiteralPath $RepositoryRoot -PathType Container)) {
    Write-Output "ERROR: Repository root does not exist: $RepositoryRoot"
    exit 1
}

if (-not (Test-Path -LiteralPath $DocsRoot -PathType Container)) {
    Write-Output "ERROR: Docs root does not exist: $DocsRoot"
    exit 1
}

Test-RequiredFiles
$markdownFiles = Get-BaselineMarkdownFiles

foreach ($file in $markdownFiles) {
    $content = Get-Content -Raw -LiteralPath $file.FullName
    $contentWithoutFences = Remove-FencedCodeBlocks $content

    Test-Metadata -File $file -Content $contentWithoutFences
    Test-AbsoluteLocalPaths -File $file -Content $contentWithoutFences
    Test-UnfinishedWorkMarkers -File $file -Content $contentWithoutFences
    Test-MarkdownLinks -File $file -Content $contentWithoutFences
    Test-RepositoryPathReferences -File $file -Content $contentWithoutFences
}

if ($errors.Count -gt 0) {
    foreach ($validationError in $errors) {
        Write-Output "ERROR: $validationError"
    }

    Write-Output "Documentation validation failed with $($errors.Count) error(s)."
    exit 1
}

Write-Output "Documentation validation passed for $($markdownFiles.Count) Markdown file(s)."
exit 0
