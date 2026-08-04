<?php

namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\Enum\SaleMode;
use App\Catalog\Domain\Enum\VariantStatus;
use App\Catalog\Infrastructure\Persistence\Doctrine\DoctrineProductVariantRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineProductVariantRepository::class)]
#[ORM\Table(name: 'product_variants')]
#[ORM\UniqueConstraint(name: 'uniq_variant_sku', columns: ['sku'])]
#[ORM\UniqueConstraint(name: 'uniq_variant_combo', columns: ['product_id', 'unit_of_measure_id', 'sale_mode'])]
class ProductVariant implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(length: 100)]
    private string $sku;

    #[ORM\ManyToOne(targetEntity: UnitOfMeasure::class)]
    #[ORM\JoinColumn(nullable: false)]
    private UnitOfMeasure $unitOfMeasure;

    #[ORM\Column(enumType: SaleMode::class)]
    private SaleMode $saleMode;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $defaultPrice = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3, nullable: true)]
    private ?string $alertThreshold = null;

    #[ORM\Column(enumType: VariantStatus::class)]
    private VariantStatus $status = VariantStatus::Active;

    public function __construct(
        Product $product,
        string $sku,
        UnitOfMeasure $unitOfMeasure,
        SaleMode $saleMode,
    ) {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->product = $product;
        $this->sku = $sku;
        $this->unitOfMeasure = $unitOfMeasure;
        $this->saleMode = $saleMode;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getUnitOfMeasure(): UnitOfMeasure
    {
        return $this->unitOfMeasure;
    }

    public function getSaleMode(): SaleMode
    {
        return $this->saleMode;
    }

    public function getDefaultPrice(): string
    {
        return $this->defaultPrice;
    }

    public function setDefaultPrice(string $defaultPrice): void
    {
        $this->defaultPrice = $defaultPrice;
        $this->touch();
    }

    public function getAlertThreshold(): ?string
    {
        return $this->alertThreshold;
    }

    public function setAlertThreshold(?string $alertThreshold): void
    {
        $this->alertThreshold = $alertThreshold;
        $this->touch();
    }

    public function getStatus(): VariantStatus
    {
        return $this->status;
    }
}
