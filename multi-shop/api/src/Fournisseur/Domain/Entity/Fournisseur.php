<?php

namespace App\Fournisseur\Domain\Entity;

use App\Fournisseur\Domain\Enum\FournisseurStatus;
use App\Fournisseur\Infrastructure\Persistence\Doctrine\DoctrineFournisseurRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineFournisseurRepository::class)]
#[ORM\Table(name: 'fournisseurs')]
class Fournisseur implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(enumType: FournisseurStatus::class)]
    private FournisseurStatus $status = FournisseurStatus::Active;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(string $name)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->name = $name;
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->touch();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
        $this->touch();
    }

    public function getStatus(): FournisseurStatus
    {
        return $this->status;
    }

    public function setStatus(FournisseurStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function markDeleted(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
