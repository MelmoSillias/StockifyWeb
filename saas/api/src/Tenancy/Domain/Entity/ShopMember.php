<?php

namespace App\Tenancy\Domain\Entity;

use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use App\Tenancy\Domain\Enum\ShopMemberRole;
use App\Tenancy\Domain\Enum\ShopMemberStatus;
use App\Tenancy\Infrastructure\Persistence\Doctrine\DoctrineShopMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineShopMemberRepository::class)]
#[ORM\Table(name: 'shop_members')]
#[ORM\UniqueConstraint(name: 'uniq_shop_user', columns: ['shop_id', 'user_id'])]
class ShopMember
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Shop::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Shop $shop;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: AccountMember::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AccountMember $accountMember = null;

    #[ORM\Column(enumType: ShopMemberRole::class)]
    private ShopMemberRole $role;

    #[ORM\Column(enumType: ShopMemberStatus::class)]
    private ShopMemberStatus $status = ShopMemberStatus::Active;

    public function __construct(Shop $shop, User $user, ShopMemberRole $role, ?AccountMember $accountMember = null)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->shop = $shop;
        $this->user = $user;
        $this->role = $role;
        $this->accountMember = $accountMember;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): ShopMemberRole
    {
        return $this->role;
    }

    public function getStatus(): ShopMemberStatus
    {
        return $this->status;
    }
}
