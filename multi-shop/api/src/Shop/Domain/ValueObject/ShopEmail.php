<?php

namespace App\Shop\Domain\ValueObject;

/**
 * @deprecated Gap A — shop users no longer receive synthetic .local emails.
 */
final readonly class ShopEmail
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function forShopUser(string $username, string $shopSlug): self
    {
        $normalizedUsername = strtolower(trim($username));
        $normalizedSlug = strtolower(trim($shopSlug));

        if ('' === $normalizedUsername || '' === $normalizedSlug) {
            throw new \InvalidArgumentException('Username and shop slug are required.');
        }

        return new self(sprintf('%s@%s.local', $normalizedUsername, $normalizedSlug));
    }

    public function value(): string
    {
        return $this->value;
    }
}
