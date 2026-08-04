<?php

namespace App\Tests\Support;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

final class IntegrationJwtTestHelper
{
    private static ?string $privateKeyPath = null;
    private static ?string $publicKeyPath = null;

    /** @return array{private: string, public: string} */
    public static function ensureKeyPair(): array
    {
        if (null !== self::$privateKeyPath && null !== self::$publicKeyPath) {
            return [
                'private' => self::$privateKeyPath,
                'public' => self::$publicKeyPath,
            ];
        }

        $dir = sys_get_temp_dir().'/stockify-integration-jwt';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        self::$privateKeyPath = $dir.'/integration_private.pem';
        self::$publicKeyPath = $dir.'/integration_public.pem';

        if (!is_file(self::$privateKeyPath) || !is_file(self::$publicKeyPath)) {
            $resource = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
            ]);
            if (false === $resource) {
                throw new \RuntimeException('Unable to generate integration JWT key pair.');
            }

            openssl_pkey_export($resource, $privateKey);
            $details = openssl_pkey_get_details($resource);
            if (false === $details) {
                throw new \RuntimeException('Unable to read integration JWT public key.');
            }

            file_put_contents(self::$privateKeyPath, $privateKey);
            file_put_contents(self::$publicKeyPath, $details['key']);
        }

        putenv('INTEGRATION_JWT_PUBLIC_KEY='.self::$publicKeyPath);
        $_ENV['INTEGRATION_JWT_PUBLIC_KEY'] = self::$publicKeyPath;
        $_SERVER['INTEGRATION_JWT_PUBLIC_KEY'] = self::$publicKeyPath;
        putenv('INTEGRATION_ENABLED=1');
        $_ENV['INTEGRATION_ENABLED'] = '1';
        $_SERVER['INTEGRATION_ENABLED'] = '1';

        return [
            'private' => self::$privateKeyPath,
            'public' => self::$publicKeyPath,
        ];
    }

    public static function createToken(): string
    {
        $keys = self::ensureKeyPair();
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::file($keys['private']),
            InMemory::file($keys['public']),
        );

        $now = new \DateTimeImmutable();
        $token = $configuration->builder()
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+1 hour'))
            ->relatedTo('integration-control-plane')
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }

    /** @return array<string, string> */
    public static function authHeaders(?string $idempotencyKey = null): array
    {
        $headers = [
            'HTTP_AUTHORIZATION' => 'Bearer '.self::createToken(),
            'CONTENT_TYPE' => 'application/json',
        ];

        if (null !== $idempotencyKey) {
            $headers['HTTP_Idempotency-Key'] = $idempotencyKey;
        }

        return $headers;
    }
}
