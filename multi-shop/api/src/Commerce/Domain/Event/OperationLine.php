<?php

namespace App\Commerce\Domain\Event;

/**
 * Serializable snapshot of a commercial operation line, carried by domain
 * events so consuming modules never read Commerce's database directly.
 */
final readonly class OperationLine
{
    public function __construct(
        public ?string $variantId,
        public string $label,
        public string $quantity,
        public string $unitPrice,
        public string $lineTotal,
    ) {
    }
}
