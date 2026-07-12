param(
    [switch]$SkipComposer,
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
    php bin/console doctrine:database:create --if-not-exists
    php bin/console doctrine:migrations:migrate --no-interaction

    $fixtureArgs = @('doctrine:fixtures:load', '--no-interaction')
    if ($AppendFixtures) {
        $fixtureArgs += '--append'
    }

    Write-Host 'Loading fixtures...'
    & php bin/console @fixtureArgs

    Write-Host ''
    Write-Host 'API bootstrap complete.'
    Write-Host 'Start server:  .\scripts\start-api.ps1'
    Write-Host 'Health check:  http://localhost:8000/api/health'
} finally {
    Pop-Location
}
