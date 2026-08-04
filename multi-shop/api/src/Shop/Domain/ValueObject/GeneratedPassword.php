<?php

namespace App\Shop\Domain\ValueObject;

final readonly class GeneratedPassword
{
    private function __construct(
        private string $plainValue,
    ) {
    }

    public static function fromPlain(string $plainValue): self
    {
        if (strlen($plainValue) < 8) {
            throw new \InvalidArgumentException('Generated password is too short.');
        }

        return new self($plainValue);
    }

    public function plainValue(): string
    {
        return $this->plainValue;
    }
}
