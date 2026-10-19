param(
	[string[]]$Files
)

$ErrorActionPreference = 'Stop'

function Get-CandidateFiles {
	if ($Files -and $Files.Count -gt 0) {
		return $Files
	}

	$gitFiles = git diff --name-only --cached
	$gitFiles += git diff --name-only

	return $gitFiles |
		Where-Object { $_ -match '^client/src/.*\.(ts|tsx|js|jsx)$' } |
		Sort-Object -Unique
}

$candidateFiles = @(Get-CandidateFiles | Where-Object { Test-Path $_ })

if ($candidateFiles.Count -eq 0) {
	Write-Output 'No frontend candidate files found.'
	exit 0
}

$patterns = @(
	@{ Name = 'ts-nocheck'; Pattern = '@ts-nocheck'; Message = 'Avoid @ts-nocheck on touched/migrated frontend code.' },
	@{ Name = 'raw-api-call'; Pattern = 'apiCall\s*\('; Message = 'Avoid raw apiCall in touched surfaces; use generated clients or typed adapters.' },
	@{ Name = 'react-router-v5-props'; Pattern = 'props\.(match|history)'; Message = 'Avoid React Router v5 props on touched routes.' },
	@{ Name = 'component-will-mount'; Pattern = 'componentWillMount'; Message = 'Do not copy legacy lifecycle methods forward.' },
	@{ Name = 'redux-pattern'; Pattern = 'react-redux|connect\s*\('; Message = 'Do not revive Redux patterns for new touched/migrated code.' }
)

$findings = @()

foreach ($file in $candidateFiles) {
	$content = Get-Content -LiteralPath $file -Raw

	foreach ($pattern in $patterns) {
		if ($content -match $pattern.Pattern) {
			$findings += [PSCustomObject]@{
				File = $file
				Check = $pattern.Name
				Message = $pattern.Message
			}
		}
	}

	if ($content -match '(?is)TODO(?!\((JP-[0-9]+|no issue)\)).{0,160}(legacy adapter|temporary adapter|apiCall|v1 endpoint|generated client)') {
		$findings += [PSCustomObject]@{
			File = $file
			Check = 'weak-temporary-adapter-todo'
			Message = 'Temporary adapter TODOs should include JP issue or no issue, target, and removal condition.'
		}
	}
}

if ($findings.Count -eq 0) {
	Write-Output 'No frontend architecture warnings found.'
	exit 0
}

$findings | Format-Table -AutoSize
exit 1
