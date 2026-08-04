<?php

namespace App\Commerce\Application\Dto;

final readonly class DevisDetailDto
{
    /**
     * @param list<DevisLineDto> $lines
     */
    public function __construct(
        public string $id,
        public string $reference,
        /** @var array{client_id: string|null, anonymous_info: string|null} */
        public array $acheteur,
        public string $status,
        public string $totalAmount,
        public string $createdAt,
        public ?string $validUntil,
        public ?string $cancelledAt,
        public ?string $convertedVenteId,
        public ?string $convertedCommandeId,
        public array $lines,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'acheteur' => $this->acheteur,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'created_at' => $this->createdAt,
            'valid_until' => $this->validUntil,
            'cancelled_at' => $this->cancelledAt,
            'converted_vente_id' => $this->convertedVenteId,
            'converted_commande_id' => $this->convertedCommandeId,
            'lines' => array_map(
                static fn (DevisLineDto $line) => $line->toArray(),
                $this->lines,
            ),
        ];
    }
}
