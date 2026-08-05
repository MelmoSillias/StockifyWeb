<?php

namespace App\Integration\Security;

final class IntegrationTokenClaims
{
    public const ROLE_READ = 'ROLE_SCOPE_INTEGRATION_READ';
    public const ROLE_WRITE = 'ROLE_SCOPE_INTEGRATION_WRITE';

    /** @param list<string> $scopes */
    public function __construct(
        public readonly string $subject,
        public readonly array $scopes,
    ) {
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    /**
     * Scopes are surfaced as Symfony roles so integration endpoints can be guarded
     * with the same #[IsGranted] mechanism as the rest of the API.
     *
     * @return list<string>
     */
    public function toRoles(): array
    {
        $roles = ['ROLE_INTEGRATION'];
        foreach ($this->scopes as $scope) {
            $roles[] = 'ROLE_SCOPE_'.strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '_', $scope));
        }

        return array_values(array_unique($roles));
    }
}
