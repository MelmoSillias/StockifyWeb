<?php

namespace App\Client\Domain\Entity;

use App\Client\Domain\Enum\ClientStatus;
use App\Client\Infrastructure\Persistence\Doctrine\DoctrineClientRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineClientRepository::class)]
#[ORM\Table(name: 'clients')]
class Client
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(enumType: ClientStatus::class)]
    private ClientStatus $status = ClientStatus::Active;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $creditLimit = null;

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

    public function getStatus(): ClientStatus
    {
        return $this->status;
    }

    public function setStatus(ClientStatus $status): void
    {
        $this->status = $status;
        $this->touch();
    }

    public function getCreditLimit(): ?string
    {
        return $this->creditLimit;
    }

    public function setCreditLimit(?string $creditLimit): void
    {
        $this->creditLimit = $creditLimit;
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
