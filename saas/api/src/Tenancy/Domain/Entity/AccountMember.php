<?php

namespace App\Tenancy\Domain\Entity;

use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use App\Tenancy\Domain\Enum\AccountMemberRole;
use App\Tenancy\Domain\Enum\AccountMemberStatus;
use App\Tenancy\Infrastructure\Persistence\Doctrine\DoctrineAccountMemberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineAccountMemberRepository::class)]
#[ORM\Table(name: 'account_members')]
#[ORM\UniqueConstraint(name: 'uniq_account_user', columns: ['account_id', 'user_id'])]
class AccountMember
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'members')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(enumType: AccountMemberRole::class)]
    private AccountMemberRole $role;

    #[ORM\Column(enumType: AccountMemberStatus::class)]
    private AccountMemberStatus $status = AccountMemberStatus::Active;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $joinedAt = null;

    public function __construct(Account $account, User $user, AccountMemberRole $role)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->account = $account;
        $this->user = $user;
        $this->role = $role;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): AccountMemberRole
    {
        return $this->role;
    }

    public function getStatus(): AccountMemberStatus
    {
        return $this->status;
    }
}
