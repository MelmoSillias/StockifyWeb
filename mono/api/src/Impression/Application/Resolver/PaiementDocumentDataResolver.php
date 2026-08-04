<?php

namespace App\Impression\Application\Resolver;

use App\Commerce\Domain\Repository\CommandeRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use App\Paiement\Application\Service\PaiementSerializer;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class PaiementDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly PaiementRepositoryInterface $paiementRepository,
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly PaiementSerializer $paiementSerializer,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::Paiement === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $paiement = $this->paiementRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $paiement) {
            throw new \DomainException('Paiement not found.');
        }

        $serialized = $this->paiementSerializer->serialize($paiement);
        $contextRef = null;
        $contextLabel = null;

        if (null !== $paiement->getFactureId()) {
            $facture = $this->factureRepository->findById($paiement->getFactureId());
            $contextRef = $facture?->getNumero();
            $contextLabel = 'Facture';
        } elseif (null !== $paiement->getCommandeId()) {
            $commande = $this->commandeRepository->findById($paiement->getCommandeId());
            $contextRef = $commande?->getReference();
            $contextLabel = 'Commande';
        }

        return [
            'filename' => $paiement->getReference(),
            'title' => 'Reçu de paiement',
            'document_number' => $paiement->getReference(),
            'paid_at' => $paiement->getPaidAt()->format('d/m/Y H:i'),
            'amount' => $paiement->getAmount(),
            'method_label' => $serialized['method_label'] ?? '—',
            'context_label' => $contextLabel,
            'context_reference' => $contextRef,
            'is_cancelled' => $paiement->isCancelled(),
        ];
    }
}
