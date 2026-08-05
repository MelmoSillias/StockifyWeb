<?php

namespace App\Integration\Security;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Symfony\Component\Clock\ClockInterface;

final class IntegrationJwtValidator
{
    private ?Configuration $configuration = null;

    public function __construct(
        private readonly string $publicKeyPath,
        private readonly bool $enabled,
        private readonly ClockInterface $clock,
        private readonly string $expectedIssuer,
        private readonly string $expectedAudience,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function validate(string $token): IntegrationTokenClaims
    {
        if (!$this->enabled) {
            throw new \RuntimeException('Integration API is disabled.');
        }

        if (!is_file($this->publicKeyPath)) {
            throw new \RuntimeException('Integration JWT public key not found.');
        }

        if ('' === $this->expectedIssuer || '' === $this->expectedAudience) {
            throw new \RuntimeException('Integration JWT issuer and audience must be configured.');
        }

        $configuration = $this->getConfiguration();
        $parsed = $configuration->parser()->parse($token);

        if (!$parsed instanceof Plain) {
            throw new \InvalidArgumentException('Unsupported integration token format.');
        }

        try {
            $configuration->validator()->assert(
                $parsed,
                new SignedWith($configuration->signer(), $configuration->verificationKey()),
                new StrictValidAt($this->clock),
                new IssuedBy($this->expectedIssuer),
                new PermittedFor($this->expectedAudience),
            );
        } catch (RequiredConstraintsViolated $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), 0, $exception);
        }

        return new IntegrationTokenClaims(
            (string) $parsed->claims()->get('sub', ''),
            $this->extractScopes($parsed),
        );
    }

    /** @return list<string> */
    private function extractScopes(Plain $token): array
    {
        $scope = $token->claims()->get('scope');

        if (is_array($scope)) {
            return array_values(array_filter(array_map('strval', $scope), static fn (string $s): bool => '' !== $s));
        }

        if (!is_string($scope) || '' === trim($scope)) {
            return [];
        }

        return array_values(array_filter(preg_split('/\s+/', trim($scope)) ?: []));
    }

    private function getConfiguration(): Configuration
    {
        if (null === $this->configuration) {
            $this->configuration = Configuration::forAsymmetricSigner(
                new Sha256(),
                InMemory::plainText('unused'),
                InMemory::file($this->publicKeyPath),
            );
        }

        return $this->configuration;
    }
}
