<?php

namespace App\Integration\Application\Command\ProvisionAccount;

final readonly class ProvisionAccountCommand
{
    /**
     * @param array<string, mixed> $entitlements
     */
    public function __construct(
        public string $externalAccountId,
        public ?string $idempotencyKey,
        public array $entitlements = [],
    ) {
    }

    public static function fromPayload(array $payload, ?string $idempotencyKey): self
    {
        return new self(
            externalAccountId: (string) ($payload['external_account_id'] ?? ''),
            idempotencyKey: $idempotencyKey,
            entitlements: is_array($payload['entitlements'] ?? null) ? $payload['entitlements'] : [],
        );
    }
}
