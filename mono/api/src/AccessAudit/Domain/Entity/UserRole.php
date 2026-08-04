<?php

namespace App\AccessAudit\Domain\Entity;

use App\AccessAudit\Infrastructure\Persistence\Doctrine\DoctrineUserRoleRepository;
use App\IdentityAccess\Domain\Entity\User;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineUserRoleRepository::class)]
#[ORM\Table(name: 'user_roles')]
#[ORM\UniqueConstraint(name: 'uniq_user_role', fields: ['user', 'role'])]
class UserRole
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'userRoles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Role::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Role $role;

    public function __construct(User $user, Role $role)
    {
        $this->initializeUuid();
        $this->user = $user;
        $this->role = $role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRole(): Role
    {
        return $this->role;
    }
}
