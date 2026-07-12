<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Enum\ProductStatus;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductRepository;
use App\SharedKernel\Domain\Trait\TenantScopedTrait;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineProductRepository::class)]
#[ORM\Table(name: 'products')]
class Product
{
    use UuidEntityTrait;
    use TimestampableTrait;
    use TenantScopedTrait;

    #[ORM\ManyToOne(targetEntity: ProductCategory::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $category = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: ProductStatus::class)]
    private ProductStatus $status = ProductStatus::Active;

    public function __construct(Uuid $accountId, Uuid $shopId, string $name)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->setTenantScope($accountId, $shopId);
        $this->name = $name;
    }

    public function getCategory(): ?ProductCategory
    {
        return $this->category;
    }

    public function setCategory(?ProductCategory $category): void
    {
        $this->category = $category;
        $this->touch();
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

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): void
    {
        $this->reference = $reference;
        $this->touch();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touch();
    }

    public function getStatus(): ProductStatus
    {
        return $this->status;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function updateDetails(?string $reference = null, ?string $description = null): void
    {
        if (null !== $reference) {
            $this->reference = $reference;
        }
        if (null !== $description) {
            $this->description = $description;
        }
        $this->touch();
    }

    public function assignCategory(?ProductCategory $category): void
    {
        $this->category = $category;
        $this->touch();
    }
}
