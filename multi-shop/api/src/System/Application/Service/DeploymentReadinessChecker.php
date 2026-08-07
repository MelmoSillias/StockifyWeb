<?php

namespace App\System\Application\Service;

final class DeploymentReadinessChecker
{
    public function __construct(
        private readonly string $identityJwtPublicKeyPath,
        private readonly string $integrationJwtPublicKeyPath,
    ) {
    }

    /**
     * @return list<string>
     */
    public function missingRequirements(): array
    {
        $missing = [];

        if (!$this->isReadableKeyFile($this->identityJwtPublicKeyPath)) {
            $missing[] = 'identity_jwt_public_key';
        }

        if (!$this->isReadableKeyFile($this->integrationJwtPublicKeyPath)) {
            $missing[] = 'integration_jwt_public_key';
        }

        return $missing;
    }

    private function isReadableKeyFile(string $path): bool
    {
        return '' !== trim($path) && is_file($path) && is_readable($path);
    }
}
