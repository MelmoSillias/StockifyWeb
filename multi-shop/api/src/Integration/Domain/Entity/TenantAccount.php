<?php

namespace App\Integration\Domain\Entity;

use App\Integration\Domain\Enum\TenantAccountStatus;
use App\Integration\Infrastructure\Persistence\Doctrine\DoctrineTenantAccountRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineTenantAccountRepository::class)]
#[ORM\Table(name: 'tenant_accounts')]
#[ORM\UniqueConstraint(name: 'uniq_tenant_external_account_id', columns: ['external_account_id'])]
class TenantAccount
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(name: 'external_account_id', length: 255)]
    private string $externalAccountId;

    #[ORM\Column(enumType: TenantAccountStatus::class)]
    private TenantAccountStatus $status = TenantAccountStatus::Provisioning;

    /** @var array<string, mixed> */
    #[ORM\Column(name: 'entitlements_snapshot', type: 'json')]
    private array $entitlementsSnapshot = [];

    #[ORM\Column(name: 'provisioned_at', nullable: true)]
    private ?\DateTimeImmutable $provisionedAt = null;

    #[ORM\Column(name: 'last_synced_at', nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    /**
     * @param array<string, mixed> $entitlementsSnapshot
     */
    public function __construct(string $externalAccountId, array $entitlementsSnapshot = [])
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->externalAccountId = trim($externalAccountId);
        $this->entitlementsSnapshot = $entitlementsSnapshot;
    }

    public function getExternalAccountId(): string
    {
        return $this->externalAccountId;
    }

    public function getStatus(): TenantAccountStatus
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function getEntitlementsSnapshot(): array
    {
        return $this->entitlementsSnapshot;
    }

    public function getProvisionedAt(): ?\DateTimeImmutable
    {
        return $this->provisionedAt;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function markProvisioned(): void
    {
        $this->status = TenantAccountStatus::Active;
        $this->provisionedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function suspend(): void
    {
        $this->status = TenantAccountStatus::Suspended;
        $this->touch();
    }

    public function activate(): void
    {
        $this->status = TenantAccountStatus::Active;
        $this->touch();
    }

    /** @param array<string, mixed> $entitlements */
    public function updateEntitlements(array $entitlements): void
    {
        $this->entitlementsSnapshot = $entitlements;
        $this->lastSyncedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function isSuspended(): bool
    {
        return TenantAccountStatus::Suspended === $this->status;
    }
}
