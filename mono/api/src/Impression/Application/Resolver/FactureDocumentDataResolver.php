<?php

namespace App\Impression\Application\Resolver;

use App\Client\Domain\Repository\ClientRepositoryInterface;
use App\Facturation\Domain\Entity\Facture;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use App\Paiement\Domain\Repository\PaiementRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class FactureDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly FactureRepositoryInterface $factureRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly PaiementRepositoryInterface $paiementRepository,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::Facture === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $facture = $this->factureRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $facture) {
            throw new \DomainException('Facture not found.');
        }

        return $this->buildPayload($facture);
    }

    /** @return array<string, mixed> */
    private function buildPayload(Facture $facture): array
    {
        $clientName = $facture->getAnonymousInfo();
        if (null !== $facture->getClientId()) {
            $client = $this->clientRepository->findById($facture->getClientId());
            $clientName = $client?->getName() ?? $clientName;
        }

        $paiements = [];
        foreach ($this->paiementRepository->findByFactureId($facture->getId()) as $paiement) {
            if (!$paiement->isCancelled()) {
                $paiements[] = [
                    'reference' => $paiement->getReference(),
                    'amount' => $paiement->getAmount(),
                    'paid_at' => $paiement->getPaidAt()->format('d/m/Y H:i'),
                ];
            }
        }

        return [
            'filename' => $facture->getNumero(),
            'title' => 'Facture',
            'document_number' => $facture->getNumero(),
            'issued_at' => $facture->getIssuedAt()->format('d/m/Y H:i'),
            'source_reference' => $facture->getSourceReference(),
            'buyer_name' => $clientName ?? '—',
            'is_creance' => $facture->isCreance(),
            'credit_closed_at' => $facture->getCreditClosedAt()?->format('d/m/Y H:i'),
            'total_amount' => $facture->getTotalAmount(),
            'lines' => array_map(static fn ($line) => [
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'line_total' => $line->getLineTotal(),
            ], $facture->getLines()->toArray()),
            'paiements' => $paiements,
        ];
    }
}
