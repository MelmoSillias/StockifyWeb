<?php

namespace App\IdentityAccess\Application\Query\GetLoginHistory;

use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;

final class GetLoginHistoryHandler
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    /** @return array{data: list<array<string, mixed>>, meta: array{total: int, page: int, limit: int}} */
    public function handle(GetLoginHistoryQuery $query): array
    {
        $userId = $query->user->getId();
        $page = max(1, $query->page);
        $limit = min(50, max(1, $query->limit));

        $logs = $this->auditLogRepository->search(
            userId: $userId,
            action: 'auth.login.success',
            page: $page,
            limit: $limit,
        );

        $total = $this->auditLogRepository->countSearch(
            userId: $userId,
            action: 'auth.login.success',
        );

        $data = array_map(static fn ($log) => [
            'id' => (string) $log->getId(),
            'occurred_at' => $log->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'ip' => $log->getIp(),
            'user_agent' => $log->getUserAgent(),
            'status' => $log->getStatus()->value,
        ], $logs);

        return [
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ],
        ];
    }
}
