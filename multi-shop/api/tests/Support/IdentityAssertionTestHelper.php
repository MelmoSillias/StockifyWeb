<?php

namespace App\Tests\Support;

use Symfony\Component\Uid\Uuid;

final class IdentityAssertionTestHelper
{
    private static ?string $privateKeyPath = null;

    public static function ensureKeyPair(): void
    {
        $private = dirname(__DIR__, 2).'/config/jwt/identity_private.pem';
        $public = dirname(__DIR__, 2).'/config/jwt/identity_public.pem';
        $cpPrivate = dirname(__DIR__, 5).'/sim-saas-admin/api/config/jwt/identity_private.pem';
        $cpPublic = dirname(__DIR__, 5).'/sim-saas-admin/api/config/jwt/identity_public.pem';

        if (!is_file($private) && is_file($cpPrivate)) {
            copy($cpPrivate, $private);
        }
        if (!is_file($public) && is_file($cpPublic)) {
            copy($cpPublic, $public);
        }

        self::$privateKeyPath = is_file($private) ? $private : (is_file($cpPrivate) ? $cpPrivate : null);
    }

    /**
     * @param list<string> $accountIds
     */
    public static function createAssertion(
        string $subject,
        string $email,
        array $accountIds = [],
        string $audience = 'stockify',
        string $issuer = 'sim-saas-admin',
        bool $emailVerified = false,
    ): string {
        self::ensureKeyPair();
        if (null === self::$privateKeyPath || !is_file(self::$privateKeyPath)) {
            throw new \RuntimeException('Identity private key not available for tests.');
        }

        $privateKey = openssl_pkey_get_private(file_get_contents(self::$privateKeyPath));
        if (false === $privateKey) {
            throw new \RuntimeException('Unable to load identity private key for tests.');
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $payload = [
            'iss' => $issuer,
            'aud' => $audience,
            'sub' => $subject,
            'email' => strtolower(trim($email)),
            'accounts' => array_values($accountIds),
            'email_verified' => $emailVerified,
            'auth_provider' => 'local',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 300,
            'jti' => (string) Uuid::v7(),
        ];

        $segments = [
            self::base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            self::base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
        $signingInput = implode('.', $segments);
        $signature = '';
        if (!openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Unable to sign test identity assertion.');
        }
        $segments[] = self::base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public static function hmacHeaders(string $body): array
    {
        $secret = trim((string) (getenv('INTEGRATION_WEBHOOK_SECRET') ?: 'dev-webhook-secret-change-me'));

        return [
            'HTTP_X-Integration-Signature' => hash_hmac('sha256', $body, $secret),
            'CONTENT_TYPE' => 'application/json',
        ];
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
