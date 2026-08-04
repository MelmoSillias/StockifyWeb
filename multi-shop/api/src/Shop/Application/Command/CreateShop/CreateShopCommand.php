<?php

namespace App\Shop\Application\Command\CreateShop;

use Symfony\Component\Uid\Uuid;

final readonly class CreateShopCommand
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $currency = null,
        public ?string $address = null,
        public ?string $phone = null,
    ) {
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            name: (string) ($payload['name'] ?? ''),
            slug: (string) ($payload['slug'] ?? ''),
            currency: isset($payload['currency']) ? (string) $payload['currency'] : null,
            address: isset($payload['address']) ? (string) $payload['address'] : null,
            phone: isset($payload['phone']) ? (string) $payload['phone'] : null,
        );
    }
}
