<?php

namespace App\IdentityAccess\Security;

final readonly class IdentityAssertionClaims
{
    /**
     * @param list<string> $accounts
     */
    public function __construct(
        public string $subject,
        public string $email,
        public array $accounts,
    ) {
    }
}
