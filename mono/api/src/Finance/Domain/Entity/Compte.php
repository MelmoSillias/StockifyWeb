<?php

namespace App\Finance\Domain\Entity;

use App\Finance\Domain\Enum\CompteType;
use App\Finance\Infrastructure\Persistence\Doctrine\DoctrineCompteRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineCompteRepository::class)]
#[ORM\Table(name: 'comptes')]
class Compte
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(enumType: CompteType::class)]
    private CompteType $type;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isActive = true;

    public function __construct(string $name, CompteType $type, bool $isDefault = false)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->name = $name;
        $this->type = $type;
        $this->isDefault = $isDefault;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function getType(): CompteType
    {
        return $this->type;
    }

    public function setType(CompteType $type): void
    {
        $this->type = $type;
        $this->touch();
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
        $this->touch();
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
}
