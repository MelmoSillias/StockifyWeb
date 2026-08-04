<?php

namespace App\Commerce\Domain\Entity;

use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'commande_lines')]
class CommandeLine
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Commande $commande;

    #[ORM\Column(type: 'uuid')]
    private Uuid $variantId;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $lineTotal;

    public function __construct(
        Commande $commande,
        Uuid $variantId,
        string $label,
        string $quantity,
        string $unitPrice,
    ) {
        $this->initializeUuid();
        $this->commande = $commande;
        $this->variantId = $variantId;
        $this->label = $label;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = bcmul($quantity, $unitPrice, 2);
    }

    public function getVariantId(): Uuid
    {
        return $this->variantId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getQuantity(): string
    {
        return $this->quantity;
    }

    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }

    public function getLineTotal(): string
    {
        return $this->lineTotal;
    }
}
