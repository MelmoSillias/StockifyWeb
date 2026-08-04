<?php

namespace App\Integration\Security;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
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
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function validate(string $token): void
    {
        if (!$this->enabled) {
            throw new \RuntimeException('Integration API is disabled.');
        }

        if (!is_file($this->publicKeyPath)) {
            throw new \RuntimeException('Integration JWT public key not found.');
        }

        $configuration = $this->getConfiguration();
        $parsed = $configuration->parser()->parse($token);

        try {
            $configuration->validator()->assert(
                $parsed,
                new SignedWith($configuration->signer(), $configuration->verificationKey()),
                new StrictValidAt($this->clock),
            );
        } catch (RequiredConstraintsViolated $exception) {
            throw new \InvalidArgumentException($exception->getMessage(), 0, $exception);
        }
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
