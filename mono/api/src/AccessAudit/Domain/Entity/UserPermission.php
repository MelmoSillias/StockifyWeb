<?php

namespace App\AccessAudit\Domain\Entity;

use App\AccessAudit\Infrastructure\Persistence\Doctrine\DoctrineUserPermissionRepository;
use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineUserPermissionRepository::class)]
#[ORM\Table(name: 'user_permissions')]
#[ORM\UniqueConstraint(name: 'uniq_user_permission', fields: ['user', 'permission'])]
class UserPermission
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userPermissions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Permission::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Permission $permission;

    #[ORM\Column]
    private bool $granted;

    public function __construct(User $user, Permission $permission, bool $granted)
    {
        $this->initializeUuid();
        $this->user = $user;
        $this->permission = $permission;
        $this->granted = $granted;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public function isGranted(): bool
    {
        return $this->granted;
    }

    public function setGranted(bool $granted): void
    {
        $this->granted = $granted;
    }
}
