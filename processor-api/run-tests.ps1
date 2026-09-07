param(
    [switch]$KeepStack,

    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$PhpUnitArgs
)

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

$composeArgs = @('compose', '-f', 'docker-compose.test.yml')

try {
    & docker @composeArgs up -d --wait
    & docker @composeArgs exec -T test-runner composer test:prepare
    & docker @composeArgs exec -T test-runner composer test -- @PhpUnitArgs
}
finally {
    if (-not $KeepStack) {
        & docker @composeArgs down -v --remove-orphans
    }
    else {
        Write-Host 'KeepStack set -> stack left running (project jvma-test).'
    }
}
