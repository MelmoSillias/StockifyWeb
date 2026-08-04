<?php

namespace App\AccessAudit\Domain\Entity;

use App\AccessAudit\Infrastructure\Persistence\Doctrine\DoctrinePermissionRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrinePermissionRepository::class)]
#[ORM\Table(name: 'permissions')]
#[ORM\UniqueConstraint(name: 'uniq_permission_code', fields: ['code'])]
class Permission
{
    use UuidEntityTrait;

    #[ORM\Column(length: 100)]
    private string $code;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(length: 50)]
    private string $module;

    #[ORM\Column(length: 50)]
    private string $action;

    #[ORM\Column]
    private bool $isCritical = false;

    /** @var Collection<int, Role> */
    #[ORM\ManyToMany(targetEntity: Role::class, mappedBy: 'permissions')]
    private Collection $roles;

    public function __construct(string $code, string $label, string $module, string $action, bool $isCritical = false)
    {
        $this->initializeUuid();
        $this->code = $code;
        $this->label = $label;
        $this->module = $module;
        $this->action = $action;
        $this->isCritical = $isCritical;
        $this->roles = new ArrayCollection();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getModule(): string
    {
        return $this->module;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function isCritical(): bool
    {
        return $this->isCritical;
    }
}
