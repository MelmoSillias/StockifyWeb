<?php

namespace App\SharedKernel\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;

final readonly class VariantId
{
    public function __construct(
        private Uuid $value,
    ) {
    }

    public static function fromString(string $id): self
    {
        return new self(Uuid::fromString($id));
    }

    public function value(): Uuid
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
