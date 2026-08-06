<?php

namespace App\IdentityAccess\Security;

final readonly class IdentityAssertionClaims
{
    /**
     * @param list<string> $accountIds
     */
    public function __construct(
        public string $subject,
        public string $email,
        public array $accountIds,
        public bool $emailVerified = false,
        public string $authProvider = 'local',
    ) {
    }
}
