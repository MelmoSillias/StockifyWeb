<?php

namespace App\Facturation\Application\Dto;

final readonly class CreancePaiementDto
{
    public function __construct(
        public string $id,
        public string $reference,
        public string $amount,
        public string $method,
        public string $paidAt,
        public bool $isCancelled,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'amount' => $this->amount,
            'method' => $this->method,
            'paid_at' => $this->paidAt,
            'is_cancelled' => $this->isCancelled,
        ];
    }
}
