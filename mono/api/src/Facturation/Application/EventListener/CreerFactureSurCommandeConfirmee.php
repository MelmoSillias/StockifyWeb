<?php

namespace App\Facturation\Application\EventListener;

use App\Commerce\Domain\Event\CommandeConfirmee;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Uid\Uuid;

#[AsEventListener(event: CommandeConfirmee::class)]
final class CreerFactureSurCommandeConfirmee
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
    ) {
    }

    public function __invoke(CommandeConfirmee $event): void
    {
        $facture = Facture::forCommande($event->commandeId(), $event->reference(), $event->acheteur());

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
