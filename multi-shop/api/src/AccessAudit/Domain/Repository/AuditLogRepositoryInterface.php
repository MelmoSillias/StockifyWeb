<?php

namespace App\AccessAudit\Domain\Repository;

use App\AccessAudit\Domain\Entity\AuditLog;
use Symfony\Component\Uid\Uuid;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $log, bool $flush = true): void;

    /**
     * @return list<AuditLog>
     */
    public function search(
        ?Uuid $userId = null,
        ?string $action = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
        int $page = 1,
        int $limit = 50,
    ): array;

    public function countSearch(
        ?Uuid $userId = null,
        ?string $action = null,
        ?\DateTimeImmutable $from = null,
        ?\DateTimeImmutable $to = null,
    ): int;

    public function deleteOlderThan(\DateTimeImmutable $before): int;
}
