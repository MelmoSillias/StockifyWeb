$ErrorActionPreference = 'Stop'
$apiDir = Join-Path $PSScriptRoot '..' 'api' | Resolve-Path

Push-Location $apiDir
try {
    Write-Host 'Starting Stockify multi-shop API on http://localhost:8001'
    php -S localhost:8001 -t public
} finally {
    Pop-Location
}
