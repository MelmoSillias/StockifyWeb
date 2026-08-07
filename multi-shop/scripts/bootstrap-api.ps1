param(
    [switch]$SkipComposer,
    [switch]$FullDemo,
    [switch]$AppendFixtures
)

$ErrorActionPreference = 'Stop'
$apiDir = Join-Path $PSScriptRoot '..' 'api' | Resolve-Path

Push-Location $apiDir
try {
    if (-not (Test-Path '.env')) {
        if (Test-Path '.env.example') {
            Copy-Item '.env.example' '.env'
            Write-Host "Created api/.env from .env.example — review DATABASE_URL and secrets before production."
        } else {
            throw 'Missing api/.env and api/.env.example'
        }
    }

    if (-not $SkipComposer) {
        Write-Host 'Running composer install...'
        composer install --no-interaction
    }

    $privateKey = Join-Path $apiDir 'config' 'jwt' 'private.pem'
    if (-not (Test-Path $privateKey)) {
        Write-Host 'Generating JWT key pair...'
        php bin/console lexik:jwt:generate-keypair --overwrite
    }

    Write-Host 'Preparing database...'
    php scripts/create-database.php
    php bin/console doctrine:schema:drop --force --full-database
    php bin/console doctrine:migrations:migrate --no-interaction

    if ($FullDemo) {
        $fixtureArgs = @('doctrine:fixtures:load', '--no-interaction', '--group=demo')
        if ($AppendFixtures) {
            $fixtureArgs += '--append'
        }
        Write-Host 'Loading demo fixtures (OWNER account + catalog)...'
        & php bin/console @fixtureArgs
    } else {
        Write-Host 'Loading signup essentials (RBAC + units, no demo tenant)...'
        php scripts/run-signup-seed.php
    }

    Write-Host ''
    Write-Host 'API bootstrap complete.'
    Write-Host 'Start server:  .\scripts\start-api.ps1'
    Write-Host 'Health check:  http://localhost:8001/api/health'
    if (-not $FullDemo) {
        Write-Host 'Prerequisite: seed Control Plane with sim-saas-admin seed-dev.sql or AppFixtures.'
    }
} finally {
    Pop-Location
}
