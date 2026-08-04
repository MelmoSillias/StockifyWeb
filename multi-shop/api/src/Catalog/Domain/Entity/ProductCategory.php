<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Enum\CategoryStatus;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductCategoryRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineProductCategoryRepository::class)]
#[ORM\Table(name: 'product_categories')]
#[ORM\UniqueConstraint(name: 'uniq_category_name_parent', columns: ['name', 'parent_id'])]
class ProductCategory implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ProductCategory $parent = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column(enumType: CategoryStatus::class)]
    private CategoryStatus $status = CategoryStatus::Active;

    public function __construct(string $name, ?ProductCategory $parent = null)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->name = $name;
        $this->parent = $parent;
    }

    public function getParent(): ?ProductCategory
    {
        return $this->parent;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function getStatus(): CategoryStatus
    {
        return $this->status;
    }
}
