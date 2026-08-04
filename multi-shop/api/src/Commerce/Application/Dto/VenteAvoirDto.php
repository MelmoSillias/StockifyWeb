<?php

namespace App\Commerce\Application\Dto;

final readonly class VenteAvoirDto
{
    public function __construct(
        public string $id,
        public string $numero,
        public string $totalAmount,
        public string $issuedAt,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'total_amount' => $this->totalAmount,
            'issued_at' => $this->issuedAt,
        ];
    }
}
