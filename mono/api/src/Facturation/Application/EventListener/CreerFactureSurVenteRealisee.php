<?php

namespace App\Facturation\Application\EventListener;

use App\Commerce\Domain\Event\VenteRealisee;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: VenteRealisee::class)]
final class CreerFactureSurVenteRealisee
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
    ) {
    }

    public function __invoke(VenteRealisee $event): void
    {
        $facture = Facture::forVente($event->venteId(), $event->reference(), $event->acheteur());

        foreach ($event->lines() as $line) {
            $facture->addLine(
                Uuid::fromString($line->variantId),
                $line->label,
                $line->quantity,
                $line->unitPrice,
                $line->lineTotal,
            );
        }

        $this->factureRepository->save($facture);
    }
}
