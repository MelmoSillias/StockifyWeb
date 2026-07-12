<?php

namespace App\Inventory\Domain\Entity;

use App\Inventory\Infrastructure\Persistence\Doctrine\DoctrineStockLotAllocationRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineStockLotAllocationRepository::class)]
#[ORM\Table(name: 'stock_lot_allocations')]
class StockLotAllocation
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: StockMovement::class, inversedBy: 'allocations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StockMovement $movement;

    #[ORM\ManyToOne(targetEntity: StockLot::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private StockLot $lot;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 4)]
    private string $unitCost;

    public function __construct(StockMovement $movement, StockLot $lot, string $quantity, string $unitCost)
    {
        $this->initializeUuid();
        $this->movement = $movement;
        $this->lot = $lot;
        $this->quantity = $quantity;
        $this->unitCost = $unitCost;
        $movement->addAllocation($this);
    }

    public function getLot(): StockLot
    {
        return $this->lot;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }
}
