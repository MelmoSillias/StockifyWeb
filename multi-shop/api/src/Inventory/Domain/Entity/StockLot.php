<?php

namespace App\Inventory\Domain\Entity;

use App\Catalog\Domain\Entity\ProductVariant;
use App\Inventory\Infrastructure\Persistence\Doctrine\DoctrineStockLotRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineStockLotRepository::class)]
#[ORM\Table(name: 'stock_lots')]
class StockLot implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $variant;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantityInitial;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantityRemaining;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4)]
    private string $unitCost;

    #[ORM\Column]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $supplierRef = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $fournisseurId = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiryDate = null;

    public function __construct(
        ProductVariant $variant,
        string $quantity,
        string $unitCost,
        ?string $reference = null,
        ?string $supplierRef = null,
        ?\DateTimeImmutable $expiryDate = null,
        ?Uuid $fournisseurId = null,
    ) {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->variant = $variant;
        $this->quantityInitial = $quantity;
        $this->quantityRemaining = $quantity;
        $this->unitCost = $unitCost;
        $this->reference = $reference;
        $this->supplierRef = $supplierRef;
        $this->fournisseurId = $fournisseurId;
        $this->expiryDate = $expiryDate;
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getVariant(): ProductVariant
    {
        return $this->variant;
    }

    public function getQuantityInitial(): string
    {
        return $this->quantityInitial;
    }

    public function getQuantityRemaining(): string
    {
        return $this->quantityRemaining;
    }

    public function getUnitCost(): string
    {
        return $this->unitCost;
    }

    public function getReceivedAt(): \DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getExpiryDate(): ?\DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function getSupplierRef(): ?string
    {
        return $this->supplierRef;
    }

    public function getFournisseurId(): ?Uuid
    {
        return $this->fournisseurId;
    }

    public function consume(string $quantity): void
    {
        if (bccomp($this->quantityRemaining, $quantity, 3) < 0) {
            throw new \InvalidArgumentException('Lot quantity insufficient.');
        }
        $this->quantityRemaining = bcsub($this->quantityRemaining, $quantity, 3);
        $this->touch();
    }

    public function increase(string $quantity): void
    {
        if (bccomp($quantity, '0', 3) <= 0) {
            throw new \InvalidArgumentException('Increase quantity must be positive.');
        }

        $this->quantityRemaining = bcadd($this->quantityRemaining, $quantity, 3);
        $this->touch();
    }
}
