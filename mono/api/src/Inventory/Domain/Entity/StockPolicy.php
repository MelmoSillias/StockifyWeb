<?php

namespace App\Inventory\Domain\Entity;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Domain\Enum\StockPolicyStrategy;
use App\Inventory\Infrastructure\Persistence\Doctrine\DoctrineStockPolicyRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineStockPolicyRepository::class)]
#[ORM\Table(name: 'stock_policies')]
#[ORM\UniqueConstraint(name: 'uniq_policy_variant', fields: ['variant'])]
class StockPolicy
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\OneToOne(targetEntity: ProductVariant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $variant;

    #[ORM\Column(enumType: StockPolicyStrategy::class)]
    private StockPolicyStrategy $strategy;

    public function __construct(ProductVariant $variant, StockPolicyStrategy $strategy)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->variant = $variant;
        $this->strategy = $strategy;
    }

    public function getVariant(): ProductVariant
    {
        return $this->variant;
    }

    public function getStrategy(): StockPolicyStrategy
    {
        return $this->strategy;
    }

    public function setStrategy(StockPolicyStrategy $strategy): void
    {
        $this->strategy = $strategy;
        $this->touch();
    }
}
