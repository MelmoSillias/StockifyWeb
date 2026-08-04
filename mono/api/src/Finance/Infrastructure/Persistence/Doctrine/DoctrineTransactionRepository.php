<?php

namespace App\Finance\Infrastructure\Persistence\Doctrine;

use App\Finance\Domain\Entity\Transaction;
use App\Finance\Domain\Enum\TransactionSourceType;
use App\Finance\Domain\Enum\TransactionType;
use App\Finance\Domain\Repository\TransactionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class DoctrineTransactionRepository extends ServiceEntityRepository implements TransactionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findAll(?Uuid $compteId = null, ?TransactionType $type = null, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.occurredAt', 'DESC');

        if (null !== $compteId) {
            $qb->andWhere('t.compteId = :compteId')
                ->setParameter('compteId', $compteId, 'uuid');
        }

        if (null !== $type) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', $type);
        }

        if (null !== $from) {
            $qb->andWhere('t.occurredAt >= :from')
                ->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('t.occurredAt <= :to')
                ->setParameter('to', $to);
        }

        return $qb->getQuery()->getResult();
    }

    public function findById(Uuid $id): ?Transaction
    {
        return $this->find($id);
    }

    public function findBySource(TransactionSourceType $sourceType, Uuid $sourceId): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.sourceType = :sourceType')
            ->andWhere('t.sourceId = :sourceId')
            ->setParameter('sourceType', $sourceType)
            ->setParameter('sourceId', $sourceId, 'uuid')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByCompteId(Uuid $compteId): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.compteId = :compteId')
            ->setParameter('compteId', $compteId, 'uuid')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function computeBalance(Uuid $compteId): string
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.type AS type', 't.amount AS amount')
            ->andWhere('t.compteId = :compteId')
            ->andWhere('t.cancelledAt IS NULL')
            ->setParameter('compteId', $compteId, 'uuid')
            ->getQuery()
            ->getArrayResult();

        $balance = '0.00';
        foreach ($rows as $row) {
            $type = $row['type'] instanceof TransactionType ? $row['type'] : TransactionType::from((string) $row['type']);
            if (TransactionType::Revenu === $type) {
                $balance = bcadd($balance, (string) $row['amount'], 2);
            } else {
                $balance = bcsub($balance, (string) $row['amount'], 2);
            }
        }

        return $balance;
    }

    public function save(Transaction $transaction, bool $flush = true): void
    {
        $this->getEntityManager()->persist($transaction);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
