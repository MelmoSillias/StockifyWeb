<?php

namespace App\IdentityAccess\Security;

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

final class IdentityAssertionValidator
{
    private ?Configuration $configuration = null;

    public function __construct(
        private readonly string $publicKeyPath,
        private readonly ClockInterface $clock,
        private readonly string $expectedIssuer,
        private readonly string $expectedAudience,
    ) {
    }

    public function validate(string $token): IdentityAssertionClaims
    {
        if (!is_file($this->publicKeyPath)) {
            throw new \RuntimeException('Identity JWT public key not found.');
        }

        if ('' === $this->expectedIssuer || '' === $this->expectedAudience) {
            throw new \RuntimeException('Identity JWT issuer and audience must be configured.');
        }

        $configuration = $this->getConfiguration();
        $parsed = $configuration->parser()->parse($token);

        if (!$parsed instanceof Plain) {
            throw new \InvalidArgumentException('Unsupported identity assertion format.');
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

        $subject = (string) $parsed->claims()->get('sub', '');
        if ('' === $subject) {
            throw new \InvalidArgumentException('Identity assertion is missing subject.');
        }

        return new IdentityAssertionClaims(
            $subject,
            (string) $parsed->claims()->get('email', ''),
            $this->extractAccounts($parsed),
        );
    }

    /** @return list<string> */
    private function extractAccounts(Plain $token): array
    {
        $accounts = $token->claims()->get('accounts');
        if (!is_array($accounts)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $accounts), static fn (string $id): bool => '' !== $id));
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
