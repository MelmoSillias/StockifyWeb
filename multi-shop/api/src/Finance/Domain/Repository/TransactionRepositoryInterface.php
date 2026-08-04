<?php

namespace App\Finance\Domain\Repository;

use App\Finance\Domain\Entity\Transaction;
use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use Symfony\Component\Uid\Uuid;

interface TransactionRepositoryInterface
{
    /**
     * @return Transaction[]
     */
    public function findAll(?Uuid $compteId = null, ?TransactionType $type = null, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array;

    public function findById(Uuid $id): ?Transaction;

    public function findBySource(TransactionSourceType $sourceType, Uuid $sourceId): ?Transaction;

    public function countByCompteId(Uuid $compteId): int;

    public function computeBalance(Uuid $compteId): string;

    public function save(Transaction $transaction, bool $flush = true): void;
}
