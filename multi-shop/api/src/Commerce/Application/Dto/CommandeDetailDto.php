<?php

namespace App\Commerce\Application\Dto;

final readonly class CommandeDetailDto
{
    /**
     * @param list<VenteLineDto>     $lines
     * @param list<VentePaiementDto> $paiements
     */
    public function __construct(
        public string $id,
        public string $reference,
        /** @var array{client_id: string|null, anonymous_info: string|null, client_name?: string|null} */
        public array $acheteur,
        public string $status,
        public string $totalAmount,
        public string $depositReceived,
        public ?string $deliveryDate,
        public string $createdAt,
        public ?string $confirmedAt,
        public ?string $cancelledAt,
        public array $lines,
        public ?VenteFactureDto $facture,
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
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'deposit_received' => $this->depositReceived,
            'delivery_date' => $this->deliveryDate,
            'created_at' => $this->createdAt,
            'confirmed_at' => $this->confirmedAt,
            'cancelled_at' => $this->cancelledAt,
            'lines' => array_map(
                static fn (VenteLineDto $line) => $line->toArray(),
                $this->lines,
            ),
            'facture' => $this->facture?->toArray(),
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
