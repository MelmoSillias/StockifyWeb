<?php

namespace App\AccessAudit\Domain\Entity;

use App\AccessAudit\Infrastructure\Persistence\Doctrine\DoctrineRoleRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineRoleRepository::class)]
#[ORM\Table(name: 'roles')]
#[ORM\UniqueConstraint(name: 'uniq_role_code', fields: ['code'])]
class Role
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 50)]
    private string $code;

    #[ORM\Column(length: 100)]
    private string $label;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private bool $isSystem = false;

    #[ORM\Column]
    private bool $isActive = true;

    /** @var Collection<int, Permission> */
    #[ORM\ManyToMany(targetEntity: Permission::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_permissions')]
    private Collection $permissions;

    public function __construct(string $code, string $label, ?string $description = null, bool $isSystem = false)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->code = $code;
        $this->label = $label;
        $this->description = $description;
        $this->isSystem = $isSystem;
        $this->permissions = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
        $this->touch();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->touch();
    }

    /** @return Collection<int, Permission> */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): void
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $this->touch();
        }
    }

    public function removePermission(Permission $permission): void
    {
        if ($this->permissions->removeElement($permission)) {
            $this->touch();
        }
    }

    public function clearPermissions(): void
    {
        $this->permissions->clear();
        $this->touch();
    }

    public function setPermissions(iterable $permissions): void
    {
        $this->permissions->clear();
        foreach ($permissions as $permission) {
            $this->permissions->add($permission);
        }
        $this->touch();
    }

    /** @return list<string> */
    public function getPermissionCodes(): array
    {
        return $this->permissions->map(static fn (Permission $p): string => $p->getCode())->toArray();
    }
}
