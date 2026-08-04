<?php

namespace App\Shop\Application\Command\CreateShopUser;

use Symfony\Component\Uid\Uuid;

final readonly class CreateShopUserCommand
{
    /**
     * @param list<string> $roleCodes
     */
    public function __construct(
        public Uuid $shopId,
        public string $username,
        public string $firstName,
        public string $lastName,
        public array $roleCodes,
    ) {
    }

    public static function fromPayload(Uuid $shopId, array $payload): self
    {
        $roleCodes = $payload['roles'] ?? $payload['role_codes'] ?? [];
        if (!is_array($roleCodes)) {
            $roleCodes = [];
        }

        return new self(
            shopId: $shopId,
            username: (string) ($payload['username'] ?? ''),
            firstName: (string) ($payload['first_name'] ?? ''),
            lastName: (string) ($payload['last_name'] ?? ''),
            roleCodes: array_values(array_map('strval', $roleCodes)),
        );
    }
}
