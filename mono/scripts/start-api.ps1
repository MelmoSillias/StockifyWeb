$ErrorActionPreference = 'Stop'
$apiDir = Join-Path $PSScriptRoot '..' 'api' | Resolve-Path

Push-Location $apiDir
try {
    Write-Host 'Starting Stockify mono API on http://localhost:8000'
    php -S localhost:8000 -t public
} finally {
    Pop-Location
}
