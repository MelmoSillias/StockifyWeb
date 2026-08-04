<?php

namespace App\Impression\Application\Resolver;

use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use App\Livraison\Domain\Repository\BonDeLivraisonRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class BonLivraisonDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly BonDeLivraisonRepositoryInterface $bonDeLivraisonRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::BonLivraison === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $bon = $this->bonDeLivraisonRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $bon) {
            throw new \DomainException('Bon de livraison not found.');
        }

        $commande = $this->commandeRepository->findById($bon->getCommandeId());

        return [
            'filename' => $bon->getReference(),
            'title' => 'Bon de livraison',
            'document_number' => $bon->getReference(),
            'commande_reference' => $commande?->getReference(),
            'status' => $bon->getStatus()->value,
            'sent_at' => $bon->getSentAt()->format('d/m/Y H:i'),
            'delivered_at' => $bon->getDeliveredAt()?->format('d/m/Y H:i'),
            'lines' => array_map(static fn ($line) => [
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
            ], $bon->getLines()->toArray()),
        ];
    }
}
