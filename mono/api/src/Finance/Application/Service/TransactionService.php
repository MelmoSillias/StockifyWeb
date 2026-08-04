<?php

namespace App\Finance\Application\Service;

use App\Finance\Domain\Entity\Transaction;
use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use App\Finance\Domain\Repository\CompteRepositoryInterface;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly CompteRepositoryInterface $compteRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createManual(array $payload): Transaction
    {
        if (empty($payload['compte_id'])) {
            throw new \InvalidArgumentException('compte_id is required.');
        }
        if (empty($payload['type'])) {
            throw new \InvalidArgumentException('type is required.');
        }
        if (empty($payload['amount'])) {
            throw new \InvalidArgumentException('amount is required.');
        }
        if (empty($payload['label'])) {
            throw new \InvalidArgumentException('label is required.');
        }

        $compteId = Uuid::fromString((string) $payload['compte_id']);
        $compte = $this->compteRepository->findById($compteId);
        if (null === $compte || !$compte->isActive()) {
            throw new \InvalidArgumentException('Unknown or inactive account.');
        }

        $transaction = new Transaction(
            $compteId,
            TransactionType::from((string) $payload['type']),
            (string) $payload['amount'],
            (string) $payload['label'],
            $this->parseOccurredAt($payload['occurred_at'] ?? null),
            TransactionSourceType::Manuel,
            null,
            !empty($payload['description']) ? (string) $payload['description'] : null,
        );

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function createFromPaiement(
        Uuid $paiementId,
        Uuid $compteId,
        string $amount,
        string $label,
        \DateTimeImmutable $occurredAt,
    ): Transaction {
        $existing = $this->transactionRepository->findBySource(TransactionSourceType::Paiement, $paiementId);
        if (null !== $existing) {
            return $existing;
        }

        $transaction = new Transaction(
            $compteId,
            TransactionType::Revenu,
            $amount,
            $label,
            $occurredAt,
            TransactionSourceType::Paiement,
            $paiementId,
        );

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function cancel(Transaction $transaction): void
    {
        $transaction->cancel();
        $this->transactionRepository->save($transaction);
    }

    public function cancelByPaiementId(Uuid $paiementId): void
    {
        $transaction = $this->transactionRepository->findBySource(TransactionSourceType::Paiement, $paiementId);
        if (null === $transaction || $transaction->isCancelled()) {
            return;
        }

        $this->cancel($transaction);
    }

    public function createFromPaiementFournisseur(
        Uuid $paiementFournisseurId,
        Uuid $compteId,
        string $amount,
        string $label,
        \DateTimeImmutable $occurredAt,
    ): Transaction {
        $existing = $this->transactionRepository->findBySource(TransactionSourceType::PaiementFournisseur, $paiementFournisseurId);
        if (null !== $existing) {
            return $existing;
        }

        $transaction = new Transaction(
            $compteId,
            TransactionType::Depense,
            $amount,
            $label,
            $occurredAt,
            TransactionSourceType::PaiementFournisseur,
            $paiementFournisseurId,
        );

        $this->transactionRepository->save($transaction);

        return $transaction;
    }

    public function cancelByPaiementFournisseurId(Uuid $paiementFournisseurId): void
    {
        $transaction = $this->transactionRepository->findBySource(TransactionSourceType::PaiementFournisseur, $paiementFournisseurId);
        if (null === $transaction || $transaction->isCancelled()) {
            return;
        }

        $this->cancel($transaction);
    }

    private function parseOccurredAt(mixed $value): \DateTimeImmutable
    {
        if (null === $value || '' === $value) {
            return new \DateTimeImmutable();
        }

        try {
            return new \DateTimeImmutable((string) $value);
        } catch (\Exception) {
            throw new \InvalidArgumentException('Invalid occurred_at datetime.');
        }
    }
}
