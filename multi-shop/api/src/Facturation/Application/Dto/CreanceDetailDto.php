<?php

namespace App\Facturation\Application\Dto;

final readonly class CreanceDetailDto
{
    /**
     * @param list<CreancePaiementDto> $paiements
     */
    public function __construct(
        public string $id,
        public string $numero,
        public string $clientId,
        public string $clientName,
        public ?string $venteId,
        public string $sourceReference,
        public string $totalAmount,
        public string $paidAmount,
        public string $balance,
        public bool $isCreance,
        public ?string $creditClosedAt,
        public string $issuedAt,
        public string $statut,
        public bool $isCancelled,
        public array $paiements,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'client_id' => $this->clientId,
            'client_name' => $this->clientName,
            'vente_id' => $this->venteId,
            'source_reference' => $this->sourceReference,
            'total_amount' => $this->totalAmount,
            'paid_amount' => $this->paidAmount,
            'balance' => $this->balance,
            'is_creance' => $this->isCreance,
            'credit_closed_at' => $this->creditClosedAt,
            'issued_at' => $this->issuedAt,
            'statut' => $this->statut,
            'is_cancelled' => $this->isCancelled,
            'paiements' => array_map(
                static fn (CreancePaiementDto $paiement) => $paiement->toArray(),
                $this->paiements,
            ),
        ];
    }
}
