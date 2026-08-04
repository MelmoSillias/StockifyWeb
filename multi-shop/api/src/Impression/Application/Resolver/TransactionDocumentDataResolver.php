<?php

namespace App\Impression\Application\Resolver;

use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use App\Impression\Domain\Enum\DocumentType;
use App\Impression\Domain\ValueObject\DocumentRequest;
use Symfony\Component\Uid\Uuid;

final class TransactionDocumentDataResolver implements DocumentDataResolverInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CompteRepositoryInterface $compteRepository,
    ) {
    }

    public function supports(DocumentType $type): bool
    {
        return DocumentType::Transaction === $type;
    }

    public function resolve(DocumentRequest $request): array
    {
        $transaction = $this->transactionRepository->findById(Uuid::fromString((string) $request->entityId));
        if (null === $transaction) {
            throw new \DomainException('Transaction not found.');
        }

        $compte = $this->compteRepository->findById($transaction->getCompteId());

        return [
            'filename' => 'transaction-'.substr((string) $transaction->getId(), 0, 8),
            'title' => 'Relevé de transaction',
            'document_number' => (string) $transaction->getId(),
            'occurred_at' => $transaction->getOccurredAt()->format('d/m/Y H:i'),
            'compte_name' => $compte?->getName() ?? '—',
            'type' => $transaction->getType()->value,
            'amount' => $transaction->getAmount(),
            'label' => $transaction->getLabel(),
            'description' => $transaction->getDescription(),
            'source_type' => $transaction->getSourceType()->value,
            'is_cancelled' => null !== $transaction->getCancelledAt(),
        ];
    }
}
