<?php

namespace App\Commerce\Application\Dto;

final readonly class VenteLineDto
{
    public function __construct(
        public string $id,
        public string $variantId,
        public string $label,
        public string $quantity,
        public string $unitPrice,
        public string $lineTotal,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'variant_id' => $this->variantId,
            'label' => $this->label,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'line_total' => $this->lineTotal,
        ];
    }
}
