<?php

namespace App\Shop\Application\Command\UpdateShop;

use Symfony\Component\Uid\Uuid;

final readonly class UpdateShopCommand
{
    public function __construct(
        public Uuid $shopId,
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $currency = null,
        public ?string $address = null,
        public ?string $phone = null,
        public ?string $status = null,
    ) {
    }

    public static function fromPayload(Uuid $shopId, array $payload): self
    {
        return new self(
            shopId: $shopId,
            name: isset($payload['name']) ? (string) $payload['name'] : null,
            slug: isset($payload['slug']) ? (string) $payload['slug'] : null,
            currency: array_key_exists('currency', $payload) ? ($payload['currency'] !== null ? (string) $payload['currency'] : null) : null,
            address: array_key_exists('address', $payload) ? ($payload['address'] !== null ? (string) $payload['address'] : null) : null,
            phone: array_key_exists('phone', $payload) ? ($payload['phone'] !== null ? (string) $payload['phone'] : null) : null,
            status: isset($payload['status']) ? (string) $payload['status'] : null,
        );
    }
}
