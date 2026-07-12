<?php

namespace App\Inventory\Domain\Entity;

use App\Catalog\Domain\Entity\ProductVariant;
use App\IdentityAccess\Domain\Entity\User;
use App\Inventory\Domain\Enum\MovementDirection;
use App\Inventory\Domain\Enum\MovementType;
use App\Inventory\Infrastructure\Persistence\Doctrine\DoctrineStockMovementRepository;
use App\SharedKernel\Domain\Trait\TenantScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineStockMovementRepository::class)]
#[ORM\Table(name: 'stock_movements')]
class StockMovement
{
    use UuidEntityTrait;
    use TenantScopedTrait;

    #[ORM\ManyToOne(targetEntity: ProductVariant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ProductVariant $variant;

    #[ORM\Column(enumType: MovementType::class)]
    private MovementType $type;

    #[ORM\Column(enumType: MovementDirection::class)]
    private MovementDirection $direction;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4, nullable: true)]
    private ?string $unitCost = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceRef = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $performedBy = null;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, StockLotAllocation> */
    #[ORM\OneToMany(targetEntity: StockLotAllocation::class, mappedBy: 'movement', cascade: ['persist'])]
    private Collection $allocations;

    public function __construct(
        Uuid $accountId,
        Uuid $shopId,
        ProductVariant $variant,
        MovementType $type,
        MovementDirection $direction,
        string $quantity,
        ?string $unitCost = null,
        ?string $reason = null,
        ?User $performedBy = null,
    ) {
        $this->initializeUuid();
        $this->setTenantScope($accountId, $shopId);
        $this->variant = $variant;
        $this->type = $type;
        $this->direction = $direction;
        $this->quantity = $quantity;
        $this->unitCost = $unitCost;
        $this->reason = $reason;
        $this->performedBy = $performedBy;
        $this->occurredAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->allocations = new ArrayCollection();
    }

    public function getVariant(): ProductVariant
    {
        return $this->variant;
    }

    public function getType(): MovementType
    {
        return $this->type;
    }

    public function getDirection(): MovementDirection
    {
        return $this->direction;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function addAllocation(StockLotAllocation $allocation): void
    {
        $this->allocations->add($allocation);
    }

    /** @return Collection<int, StockLotAllocation> */
    public function getAllocations(): Collection
    {
        return $this->allocations;
    }
}
