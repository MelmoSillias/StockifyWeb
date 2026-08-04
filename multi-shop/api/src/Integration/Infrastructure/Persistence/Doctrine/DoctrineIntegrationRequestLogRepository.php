<?php

namespace App\Integration\Infrastructure\Persistence\Doctrine;

use App\Integration\Domain\Entity\IntegrationRequestLog;
use App\Integration\Domain\Repository\IntegrationRequestLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IntegrationRequestLog>
 */
class DoctrineIntegrationRequestLogRepository extends ServiceEntityRepository implements IntegrationRequestLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IntegrationRequestLog::class);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?IntegrationRequestLog
    {
        return $this->findOneBy(['idempotencyKey' => trim($idempotencyKey)]);
    }

    public function save(IntegrationRequestLog $log, bool $flush = true): void
    {
        $this->getEntityManager()->persist($log);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
