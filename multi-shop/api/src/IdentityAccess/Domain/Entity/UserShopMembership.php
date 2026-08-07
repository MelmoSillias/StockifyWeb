<?php

namespace App\IdentityAccess\Domain\Entity;

use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Attaches a user to a shop for multi-shop membership within a tenant.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_shop_memberships')]
#[ORM\UniqueConstraint(name: 'uniq_user_shop', columns: ['user_id', 'shop_id'])]
class UserShopMembership
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'shopMemberships')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: 'uuid')]
    private Uuid $shopId;

    #[ORM\Column]
    private bool $isPrimary;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Uuid $shopId, bool $isPrimary = false)
    {
        $this->initializeUuid();
        $this->user = $user;
        $this->shopId = $shopId;
        $this->isPrimary = $isPrimary;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getShopId(): Uuid
    {
        return $this->shopId;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function markAsPrimary(): void
    {
        $this->isPrimary = true;
    }

    public function demoteFromPrimary(): void
    {
        $this->isPrimary = false;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
