<?php

namespace App\Integration\Application\Command\SyncIdentityState;

final readonly class SyncIdentityStateCommand
{
    public function __construct(
        public string $identityId,
        public ?string $emailVerifiedAt,
    ) {
    }

    public static function fromPayload(string $identityId, array $payload): self
    {
        $emailVerifiedAt = isset($payload['email_verified_at']) ? (string) $payload['email_verified_at'] : null;

        return new self($identityId, '' === trim((string) $emailVerifiedAt) ? null : $emailVerifiedAt);
    }
}
