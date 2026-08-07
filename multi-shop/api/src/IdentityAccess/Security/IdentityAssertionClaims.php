<?php

namespace App\IdentityAccess\Security;

final readonly class IdentityAssertionClaims
{
    /**
     * @param list<string> $accountIds
     * @param list<string> $authMethods
     */
    public function __construct(
        public string $subject,
        public string $email,
        public array $accountIds,
        public bool $emailVerified = false,
        public array $authMethods = ['local'],
    ) {
    }
}
