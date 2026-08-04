<?php

namespace App\Shop\Domain\ValueObject;

final readonly class ShopUsername
{
    private function __construct(
        private string $value,
    ) {
    }

    public static function fromString(string $username): self
    {
        $normalized = strtolower(trim($username));

        if ('' === $normalized) {
            throw new \InvalidArgumentException('Username is required.');
        }

        if (!preg_match('/^[a-z0-9._-]{2,50}$/', $normalized)) {
            throw new \InvalidArgumentException('Invalid username format.');
        }

        return new self($normalized);
    }

    public function value(): string
    {
        return $this->value;
    }
}
