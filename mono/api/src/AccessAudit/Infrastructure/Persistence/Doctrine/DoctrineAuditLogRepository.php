<?php

namespace App\AccessAudit\Infrastructure\Persistence\Doctrine;

use App\AccessAudit\Domain\Entity\AuditLog;
use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class DoctrineAuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $log, bool $flush = true): void
    {
        $this->getEntityManager()->persist($log);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function search(
        ?Uuid $userId = null,
        ?string $action = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        int $page = 1,
        int $limit = 50,
    ): array {
        $qb = $this->createSearchQueryBuilder($userId, $action, $from, $to);

        return $qb
            ->orderBy('a.occurredAt', 'DESC')
            ->setFirstResult(max(0, ($page - 1) * $limit))
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countSearch(
        ?Uuid $userId = null,
        ?string $action = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): int {
        return (int) $this->createSearchQueryBuilder($userId, $action, $from, $to)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOlderThan(\DateTimeImmutable $before): int
    {
        return $this->createQueryBuilder('a')
            ->delete()
            ->andWhere('a.occurredAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    private function createSearchQueryBuilder(
        ?Uuid $userId,
        ?string $action,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
    ) {
        $qb = $this->createQueryBuilder('a');

        if ($userId !== null) {
            $qb->andWhere('a.userId = :userId')->setParameter('userId', $userId, 'uuid');
        }

        if ($action !== null && $action !== '') {
            $qb->andWhere('a.action LIKE :action')->setParameter('action', '%'.$action.'%');
        }

        if ($from !== null) {
            $qb->andWhere('a.occurredAt >= :from')->setParameter('from', $from);
        }

        if ($to !== null) {
            $qb->andWhere('a.occurredAt <= :to')->setParameter('to', $to);
        }

        return $qb;
    }
}
