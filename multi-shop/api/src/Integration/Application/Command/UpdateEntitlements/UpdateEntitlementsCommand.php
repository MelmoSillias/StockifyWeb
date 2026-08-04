<?php

namespace App\Integration\Application\Command\UpdateEntitlements;

final readonly class UpdateEntitlementsCommand
{
    /**
     * @param array<string, mixed> $entitlements
     */
    public function __construct(
        public string $externalAccountId,
        public array $entitlements,
    ) {
    }

    public static function fromPayload(string $externalAccountId, array $payload): self
    {
        return new self(
            externalAccountId: $externalAccountId,
            entitlements: is_array($payload['entitlements'] ?? null) ? $payload['entitlements'] : [],
        );
    }
}
