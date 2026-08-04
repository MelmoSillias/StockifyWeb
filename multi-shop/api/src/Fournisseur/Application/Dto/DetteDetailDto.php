<?php

namespace App\Fournisseur\Application\Dto;

final readonly class DetteDetailDto
{
    /**
     * @param list<DettePaiementDto> $paiements
     */
    public function __construct(
        public string $id,
        public string $reference,
        public string $fournisseurId,
        public string $fournisseurName,
        public ?string $commandeFournisseurId,
        public ?string $commandeReference,
        public string $totalAmount,
        public string $paidAmount,
        public string $balance,
        public ?string $label,
        public ?string $creditClosedAt,
        public string $issuedAt,
        public string $statut,
        public array $paiements,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'fournisseur_id' => $this->fournisseurId,
            'fournisseur_name' => $this->fournisseurName,
            'commande_fournisseur_id' => $this->commandeFournisseurId,
            'commande_reference' => $this->commandeReference,
            'total_amount' => $this->totalAmount,
            'paid_amount' => $this->paidAmount,
            'balance' => $this->balance,
            'label' => $this->label,
            'credit_closed_at' => $this->creditClosedAt,
            'issued_at' => $this->issuedAt,
            'statut' => $this->statut,
            'paiements' => array_map(
                static fn (DettePaiementDto $paiement) => $paiement->toArray(),
                $this->paiements,
            ),
        ];
    }
}
