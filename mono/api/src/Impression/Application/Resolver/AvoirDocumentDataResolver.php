<?php

namespace App\Impression\Application\Resolver;

use App\Facturation\Domain\Repository\AvoirRepositoryInterface;
use App\Facturation\Domain\Repository\FactureRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use Symfony\Component\Uid\Uuid;

final class AvoirDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly AvoirRepositoryInterface $avoirRepository,
        private readonly FactureRepositoryInterface $factureRepository,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::Avoir === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $avoir = $this->avoirRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $avoir) {
            throw new \DomainException('Avoir not found.');
        }

        $facture = $this->factureRepository->findById($avoir->getFactureId());

        return [
            'filename' => $avoir->getNumero(),
            'title' => 'Avoir',
            'document_number' => $avoir->getNumero(),
            'issued_at' => $avoir->getIssuedAt()->format('d/m/Y H:i'),
            'facture_numero' => $facture?->getNumero(),
            'total_amount' => $avoir->getTotalAmount(),
            'lines' => array_map(static fn ($line) => [
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'unit_price' => $line->getUnitPrice(),
                'line_total' => $line->getLineTotal(),
            ], $avoir->getLines()->toArray()),
        ];
    }
}
