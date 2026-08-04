<?php

namespace App\AccessAudit\Application\Service;

use App\AccessAudit\Domain\Entity\AuditLog;
use App\AccessAudit\Domain\Entity\Role;
use App\AccessAudit\Domain\Enum\AuditStatus;
use App\AccessAudit\Domain\Repository\AuditLogRepositoryInterface;
use App\AccessAudit\Domain\Repository\PermissionRepositoryInterface;
use App\AccessAudit\Domain\Repository\RoleRepositoryInterface;
use App\IdentityAccess\Domain\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Uuid;

final class AuditLoggerService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository,
        private readonly string $projectDir,
    ) {
    }

    public function logAction(
        string $action,
        AuditStatus $status,
        ?User $user = null,
        ?string $resourceType = null,
        ?Uuid $resourceId = null,
        ?Request $request = null,
        ?array $payloadSummary = null,
        ?int $durationMs = null,
    ): void {
        $log = new AuditLog(
            action: $action,
            status: $status,
            userId: $user?->getId(),
            userEmail: $user?->getEmail(),
            resourceType: $resourceType,
            resourceId: $resourceId,
            method: $request?->getMethod(),
            route: $request?->attributes->get('_route'),
            ip: $request?->getClientIp(),
            userAgent: $request?->headers->get('User-Agent'),
            payloadSummary: $payloadSummary,
            durationMs: $durationMs,
        );

        $this->auditLogRepository->save($log);
        $this->appendToFile($log);
    }

    public function logLogin(?User $user, AuditStatus $status, ?Request $request = null, ?string $identifier = null): void
    {
        $this->logAction(
            action: $status === AuditStatus::Success ? 'auth.login.success' : 'auth.login.failure',
            status: $status,
            user: $user,
            resourceType: 'auth',
            request: $request,
            payloadSummary: $identifier !== null ? ['identifier' => $identifier] : null,
        );
    }

    private function appendToFile(AuditLog $log): void
    {
        $dir = $this->projectDir.'/var/log';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $line = sprintf(
            "[%s] %s %s user=%s action=%s route=%s ip=%s\n",
            $log->getOccurredAt()->format('Y-m-d H:i:s'),
            $log->getStatus()->value,
            $log->getMethod() ?? '-',
            $log->getUserEmail() ?? 'anonymous',
            $log->getAction(),
            $log->getRoute() ?? '-',
            $log->getIp() ?? '-',
        );

        @file_put_contents($dir.'/audit.log', $line, FILE_APPEND | LOCK_EX);
    }
}
