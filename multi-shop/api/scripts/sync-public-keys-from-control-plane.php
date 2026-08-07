<?php

declare(strict_types=1);

/**
 * Pulls integration + identity public keys from the Control Plane into config/jwt/.
 * Used at Stockify API container startup (prod) and can be run manually:
 *   php scripts/sync-public-keys-from-control-plane.php
 */

function resolvePath(string $path): string
{
    return str_replace('%kernel.project_dir%', dirname(__DIR__), $path);
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function writeKeyIfChanged(string $targetPath, string $pem): bool
{
    ensureDirectory(dirname($targetPath));

    if (is_file($targetPath) && hash('sha256', file_get_contents($targetPath)) === hash('sha256', $pem)) {
        return false;
    }

    file_put_contents($targetPath, $pem);

    return true;
}

$baseUrl = trim((string) (getenv('CONTROL_PLANE_BASE_URL') ?: ''));
$secret = trim((string) (getenv('INTEGRATION_WEBHOOK_SECRET') ?: ''));

if ('' === $baseUrl || '' === $secret) {
    fwrite(STDERR, "[stockify-api] Skipping public key sync: CONTROL_PLANE_BASE_URL or INTEGRATION_WEBHOOK_SECRET is not set.\n");

    exit(0);
}

$identityPublicPath = resolvePath(getenv('IDENTITY_JWT_PUBLIC_KEY') ?: '%kernel.project_dir%/config/jwt/identity_public.pem');
$integrationPublicPath = resolvePath(getenv('INTEGRATION_JWT_PUBLIC_KEY') ?: '%kernel.project_dir%/config/jwt/integration_public.pem');

$payload = '{}';
$signature = hash_hmac('sha256', $payload, $secret);
$url = rtrim($baseUrl, '/').'/api/integration/v1/public-keys/pull';

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => implode("\r\n", [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Integration-Signature: '.$signature,
        ]),
        'content' => $payload,
        'timeout' => 15,
        'ignore_errors' => true,
    ],
]);

$responseBody = @file_get_contents($url, false, $context);
$statusCode = 0;
if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
    $statusCode = (int) $matches[1];
}

if (false === $responseBody || $statusCode >= 400) {
    fwrite(STDERR, "[stockify-api] Failed to pull public keys from Control Plane (HTTP {$statusCode}).\n");
    if (is_string($responseBody) && '' !== trim($responseBody)) {
        fwrite(STDERR, "[stockify-api] Response: {$responseBody}\n");
    }

    exit(1);
}

$data = json_decode($responseBody, true);
if (!is_array($data) || !is_array($data['data'] ?? null)) {
    fwrite(STDERR, "[stockify-api] Invalid JSON response while pulling public keys.\n");
    exit(1);
}

$identityPem = (string) ($data['data']['identity_public_pem'] ?? '');
$integrationPem = (string) ($data['data']['integration_public_pem'] ?? '');

if ('' === trim($identityPem) || '' === trim($integrationPem)) {
    fwrite(STDERR, "[stockify-api] Control Plane did not return both public keys.\n");
    exit(1);
}

$identityUpdated = writeKeyIfChanged($identityPublicPath, $identityPem);
$integrationUpdated = writeKeyIfChanged($integrationPublicPath, $integrationPem);

if ($identityUpdated) {
    echo "[stockify-api] Synced identity public key to {$identityPublicPath}\n";
}

if ($integrationUpdated) {
    echo "[stockify-api] Synced integration public key to {$integrationPublicPath}\n";
}

if (!$identityUpdated && !$integrationUpdated) {
    echo "[stockify-api] Public keys already up to date.\n";
}
