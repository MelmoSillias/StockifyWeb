<?php

namespace App\Facturation\Application\EventListener;

use App\Commerce\Domain\Event\VenteAnnulee;
use App\Facturation\Domain\Entity\Avoir;
use App\Facturation\Domain\Repository\AvoirRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: VenteAnnulee::class)]
final class CreerAvoirSurVenteAnnulee
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly AvoirRepositoryInterface $avoirRepository,
    ) {
    }

    public function __invoke(VenteAnnulee $event): void
    {
        $facture = $this->factureRepository->findById($event->factureId());
        if (null === $facture) {
            return;
        }

        $avoir = new Avoir($event->factureId(), $event->venteId());

        foreach ($facture->getLines() as $line) {
            $avoir->addLine(
                $line->getVariantId(),
                $line->getLineType(),
                $line->getLabel(),
                $line->getQuantity(),
                $line->getUnitPrice(),
                $line->getLineTotal(),
            );
        }

        $this->avoirRepository->save($avoir);
    }
}
