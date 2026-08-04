<?php

namespace App\Commerce\Application\Service;

use App\Commerce\Application\Dto\DevisDetailDto;
use App\Commerce\Application\Dto\DevisLineDto;
use App\Commerce\Domain\Entity\Devis;
use App\Commerce\Domain\Entity\DevisLine;

final class DevisDetailMapper
{
    public function __construct(
        private readonly AcheteurPresenter $acheteurPresenter,
    ) {
    }

    public function map(Devis $devis): DevisDetailDto
    {
        $devis->refreshStatus();

        $lines = array_map(
            fn (DevisLine $line) => $this->mapLine($line),
            $devis->getLines()->toArray(),
        );

        return new DevisDetailDto(
            id: (string) $devis->getId(),
            reference: $devis->getReference(),
            acheteur: $this->acheteurPresenter->present($devis->getAcheteur()),
            status: $devis->getStatus()->value,
            totalAmount: $devis->getTotalAmount(),
            createdAt: $devis->getCreatedAt()->format(\DateTimeInterface::ATOM),
            validUntil: $devis->getValidUntil()?->format('Y-m-d'),
            cancelledAt: $devis->getCancelledAt()?->format(\DateTimeInterface::ATOM),
            convertedVenteId: null !== $devis->getConvertedVenteId() ? (string) $devis->getConvertedVenteId() : null,
            convertedCommandeId: null !== $devis->getConvertedCommandeId() ? (string) $devis->getConvertedCommandeId() : null,
            lines: $lines,
        );
    }

    private function mapLine(DevisLine $line): DevisLineDto
    {
        return new DevisLineDto(
            id: (string) $line->getId(),
            variantId: null !== $line->getVariantId() ? (string) $line->getVariantId() : null,
            lineType: $line->getLineType()->value,
            label: $line->getLabel(),
            quantity: $line->getQuantity(),
            unitPrice: $line->getUnitPrice(),
            lineTotal: $line->getLineTotal(),
        );
    }
}
