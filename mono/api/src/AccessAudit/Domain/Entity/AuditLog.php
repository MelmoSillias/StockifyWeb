<?php

namespace App\AccessAudit\Domain\Entity;

use App\AccessAudit\Domain\Enum\AuditStatus;
use App\AccessAudit\Infrastructure\Persistence\Doctrine\DoctrineAuditLogRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineAuditLogRepository::class)]
#[ORM\Table(name: 'audit_logs')]
#[ORM\Index(name: 'idx_audit_occurred_user', columns: ['occurred_at', 'user_id'])]
#[ORM\Index(name: 'idx_audit_action', columns: ['action'])]
class AuditLog
{
    use UuidEntityTrait;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $userId = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $userEmail = null;

    #[ORM\Column(length: 100)]
    private string $action;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resourceType = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $resourceId = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $method = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $route = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $userAgent = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $payloadSummary = null;

    #[ORM\Column(enumType: AuditStatus::class)]
    private AuditStatus $status;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs = null;

    /**
     * @param array<string, mixed>|null $payloadSummary
     */
    public function __construct(
        string $action,
        AuditStatus $status,
        ?Uuid $userId = null,
        ?string $userEmail = null,
        ?string $resourceType = null,
        ?Uuid $resourceId = null,
        ?string $method = null,
        ?string $route = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?array $payloadSummary = null,
        ?int $durationMs = null,
    ) {
        $this->initializeUuid();
        $this->occurredAt = new \DateTimeImmutable();
        $this->action = $action;
        $this->status = $status;
        $this->userId = $userId;
        $this->userEmail = $userEmail;
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->method = $method;
        $this->route = $route;
        $this->ip = $ip;
        $this->userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 512) : null;
        $this->payloadSummary = self::truncatePayload($payloadSummary);
        $this->durationMs = $durationMs;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getUserId(): ?Uuid
    {
        return $this->userId;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function getResourceId(): ?Uuid
    {
        return $this->resourceId;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getRoute(): ?string
    {
        return $this->route;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /** @return array<string, mixed>|null */
    public function getPayloadSummary(): ?array
    {
        return $this->payloadSummary;
    }

    public function getStatus(): AuditStatus
    {
        return $this->status;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    /** @param array<string, mixed>|null $payload */
    private static function truncatePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($encoded) <= 2048) {
            return $payload;
        }

        return ['truncated' => true, 'preview' => mb_substr($encoded, 0, 2000)];
    }
}
