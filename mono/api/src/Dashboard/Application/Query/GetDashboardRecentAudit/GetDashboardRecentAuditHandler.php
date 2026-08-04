<?php

namespace App\Dashboard\Application\Query\GetDashboardRecentAudit;

use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;

final class GetDashboardRecentAuditHandler
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function handle(GetDashboardRecentAuditQuery $query): array
    {
        $logs = $this->auditLogRepository->search(
            userId: null,
            action: null,
            from: null,
            to: null,
            page: 1,
            limit: $query->limit,
        );

        return array_map(static fn ($log) => [
            'id' => (string) $log->getId(),
            'occurred_at' => $log->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'user_email' => $log->getUserEmail(),
            'action' => $log->getAction(),
            'resource_type' => $log->getResourceType(),
            'status' => $log->getStatus()->value,
        ], $logs);
    }
}
