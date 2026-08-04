<?php

namespace App\Integration\Domain\Entity;

use App\Integration\Infrastructure\Persistence\Doctrine\DoctrineIntegrationRequestLogRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineIntegrationRequestLogRepository::class)]
#[ORM\Table(name: 'integration_request_logs')]
#[ORM\UniqueConstraint(name: 'uniq_integration_idempotency_key', columns: ['idempotency_key'])]
#[ORM\Index(name: 'idx_integration_log_external_account', columns: ['external_account_id'])]
class IntegrationRequestLog
{
    use UuidEntityTrait;

    #[ORM\Column(length: 10)]
    private string $method;

    #[ORM\Column(length: 255)]
    private string $path;

    #[ORM\Column(name: 'external_account_id', length: 255, nullable: true)]
    private ?string $externalAccountId = null;

    #[ORM\Column(name: 'idempotency_key', length: 255, nullable: true)]
    private ?string $idempotencyKey = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'request_summary', type: 'json', nullable: true)]
    private ?array $requestSummary = null;

    #[ORM\Column(name: 'response_status', nullable: true)]
    private ?int $responseStatus = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'response_body', type: 'json', nullable: true)]
    private ?array $responseBody = null;

    #[ORM\Column(name: 'duration_ms', nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param array<string, mixed>|null $requestSummary
     */
    public function __construct(
        string $method,
        string $path,
        ?string $externalAccountId = null,
        ?string $idempotencyKey = null,
        ?array $requestSummary = null,
    ) {
        $this->initializeUuid();
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->externalAccountId = $externalAccountId;
        $this->idempotencyKey = $idempotencyKey;
        $this->requestSummary = $requestSummary;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getExternalAccountId(): ?string
    {
        return $this->externalAccountId;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function getResponseStatus(): ?int
    {
        return $this->responseStatus;
    }

    /** @return array<string, mixed>|null */
    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    /** @param array<string, mixed>|null $responseBody */
    public function recordResponse(int $status, ?array $responseBody, ?int $durationMs): void
    {
        $this->responseStatus = $status;
        $this->responseBody = $responseBody;
        $this->durationMs = $durationMs;
    }
}
