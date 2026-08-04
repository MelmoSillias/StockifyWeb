<?php

namespace App\Livraison\Application\Service;

use App\Commerce\Domain\Entity\Commande;
use App\Commerce\Domain\Enum\CommandeStatus;
use App\Commerce\Domain\Event\OperationLine;
use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Livraison\Domain\Entity\BonDeLivraison;
use App\Livraison\Domain\Event\BonDeLivraisonEnvoye;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use App\SharedKernel\Application\Event\DomainEventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

final class LivraisonService
{
    public function __construct(
        private readonly BonDeLivraisonRepositoryInterface $bonDeLivraisonRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @return list<array{
     *     variant_id: string,
     *     label: string,
     *     ordered_quantity: string,
     *     shipped_quantity: string,
     *     remaining_quantity: string,
     *     unit_price: string
     * }>
     */
    public function getResteALivrer(Commande $commande): array
    {
        $this->assertDeliverable($commande);

        $shipped = $this->bonDeLivraisonRepository->sumShippedQuantitiesByCommandeId($commande->getId());
        $remaining = [];

        foreach ($commande->getLines() as $line) {
            $variantId = (string) $line->getVariantId();
            $ordered = $line->getQuantity();
            $alreadyShipped = $shipped[$variantId] ?? '0';
            $rest = bcsub($ordered, $alreadyShipped, 3);

            if (bccomp($rest, '0', 3) <= 0) {
                continue;
            }

            $remaining[] = [
                'variant_id' => $variantId,
                'label' => $line->getLabel(),
                'ordered_quantity' => $ordered,
                'shipped_quantity' => $alreadyShipped,
                'remaining_quantity' => $rest,
                'unit_price' => $line->getUnitPrice(),
            ];
        }

        return $remaining;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function creerBonDeLivraison(Commande $commande, array $payload): BonDeLivraison
    {
        $this->assertDeliverable($commande);

        $remainingByVariant = [];
        foreach ($this->getResteALivrer($commande) as $line) {
            $remainingByVariant[$line['variant_id']] = $line;
        }

        if ([] === $remainingByVariant) {
            throw new \DomainException('Nothing left to deliver for this order.');
        }

        $rawLines = $payload['lines'] ?? [];
        if (!\is_array($rawLines) || [] === $rawLines) {
            throw new \InvalidArgumentException('At least one delivery line is required.');
        }

        $bon = new BonDeLivraison($commande->getId());

        foreach ($rawLines as $raw) {
            if (empty($raw['variant_id']) || !isset($raw['quantity']) || '' === (string) $raw['quantity']) {
                throw new \InvalidArgumentException('Each line requires variant_id and quantity.');
            }

            $variantId = (string) $raw['variant_id'];
            if (!isset($remainingByVariant[$variantId])) {
                throw new \InvalidArgumentException('Unknown or already delivered variant: ' . $variantId);
            }

            $quantity = (string) $raw['quantity'];
            if (bccomp($quantity, '0', 3) <= 0) {
                throw new \InvalidArgumentException('Quantity must be greater than zero.');
            }

            if (bccomp($quantity, $remainingByVariant[$variantId]['remaining_quantity'], 3) > 0) {
                throw new \InvalidArgumentException('Quantity exceeds remaining amount for variant: ' . $variantId);
            }

            $bon->addLine(
                Uuid::fromString($variantId),
                $remainingByVariant[$variantId]['label'],
                $quantity,
            );
        }

        if ($bon->getLines()->isEmpty()) {
            throw new \InvalidArgumentException('At least one delivery line is required.');
        }

        $this->bonDeLivraisonRepository->save($bon);

        $this->eventDispatcher->dispatch(new BonDeLivraisonEnvoye(
            $bon->getId(),
            $bon->getReference(),
            $commande->getId(),
            $commande->getReference(),
            $this->operationLines($bon),
        ));

        $this->syncCommandeDeliveryStatus($commande);

        return $bon;
    }

    public function marquerDelivre(BonDeLivraison $bon): void
    {
        $bon->markDelivered();
        $this->bonDeLivraisonRepository->save($bon);
    }

    private function syncCommandeDeliveryStatus(Commande $commande): void
    {
        $shipped = $this->bonDeLivraisonRepository->sumShippedQuantitiesByCommandeId($commande->getId());
        $commande->syncDeliveryStatus($shipped);
        $this->commandeRepository->save($commande);
    }

    private function assertDeliverable(Commande $commande): void
    {
        if (!\in_array($commande->getStatus(), [CommandeStatus::Confirmee, CommandeStatus::PartiellementLivree], true)) {
            throw new \DomainException('Delivery notes can only be created for confirmed orders.');
        }
    }

    /** @return list<OperationLine> */
    private function operationLines(BonDeLivraison $bon): array
    {
        return array_map(static fn ($line) => new OperationLine(
            (string) $line->getVariantId(),
            $line->getLabel(),
            $line->getQuantity(),
            '0.00',
            '0.00',
        ), $bon->getLines()->toArray());
    }
}
