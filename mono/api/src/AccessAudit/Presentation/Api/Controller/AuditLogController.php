<?php

namespace App\AccessAudit\Presentation\Api\Controller;

use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class AuditLogController extends AbstractController
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
    ) {
    }

    #[Route('/audit-logs', name: 'api_audit_logs_list', methods: ['GET'])]
    #[IsGranted('access.audit.view')]
    public function list(Request $request): JsonResponse
    {
        $userId = $request->query->get('user_id');
        $action = $request->query->get('action');
        $from = $request->query->get('from');
        $to = $request->query->get('to');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, max(1, (int) $request->query->get('limit', 50)));

        $fromDate = is_string($from) && $from !== '' ? new \DateTimeImmutable($from) : null;
        $toDate = is_string($to) && $to !== '' ? new \DateTimeImmutable($to) : null;
        $userUuid = is_string($userId) && $userId !== '' ? Uuid::fromString($userId) : null;

        $logs = $this->auditLogRepository->search(
            userId: $userUuid,
            action: is_string($action) ? $action : null,
            from: $fromDate,
            to: $toDate,
            page: $page,
            limit: $limit,
        );

        $total = $this->auditLogRepository->countSearch(
            userId: $userUuid,
            action: is_string($action) ? $action : null,
            from: $fromDate,
            to: $toDate,
        );

        $data = array_map(static fn ($log) => [
            'id' => (string) $log->getId(),
            'occurred_at' => $log->getOccurredAt()->format(\DateTimeInterface::ATOM),
            'user_id' => $log->getUserId() !== null ? (string) $log->getUserId() : null,
            'user_email' => $log->getUserEmail(),
            'action' => $log->getAction(),
            'resource_type' => $log->getResourceType(),
            'resource_id' => $log->getResourceId() !== null ? (string) $log->getResourceId() : null,
            'method' => $log->getMethod(),
            'route' => $log->getRoute(),
            'ip' => $log->getIp(),
            'status' => $log->getStatus()->value,
            'duration_ms' => $log->getDurationMs(),
            'payload_summary' => $log->getPayloadSummary(),
        ], $logs);

        return $this->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
            ],
        ]);
    }
}
