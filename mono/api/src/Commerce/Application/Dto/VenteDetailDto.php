<?php

namespace App\Commerce\Application\Dto;

final readonly class VenteDetailDto
{
    /**
     * @param list<VenteLineDto>     $lines
     * @param list<VentePaiementDto> $paiements
     */
    public function __construct(
        public string $id,
        public string $reference,
        /** @var array{client_id: string|null, anonymous_info: string|null} */
        public array $acheteur,
        public string $totalAmount,
        public string $createdAt,
        public ?string $cancelledAt,
        public array $lines,
        public ?VenteFactureDto $facture,
        public ?VenteAvoirDto $avoir,
        public array $paiements,
        public string $paymentStatus,
        public string $paidAmount,
        public string $balance,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'acheteur' => $this->acheteur,
            'total_amount' => $this->totalAmount,
            'created_at' => $this->createdAt,
            'cancelled_at' => $this->cancelledAt,
            'lines' => array_map(
                static fn (VenteLineDto $line) => $line->toArray(),
                $this->lines,
            ),
            'facture' => $this->facture?->toArray(),
            'avoir' => $this->avoir?->toArray(),
            'paiements' => array_map(
                static fn (VentePaiementDto $paiement) => $paiement->toArray(),
                $this->paiements,
            ),
            'payment_status' => $this->paymentStatus,
            'paid_amount' => $this->paidAmount,
            'balance' => $this->balance,
        ];
    }
}
