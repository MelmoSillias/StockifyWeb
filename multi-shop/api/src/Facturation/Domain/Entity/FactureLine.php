<?php

namespace App\Facturation\Domain\Entity;

use App\Commerce\Domain\Enum\CommerceLineType;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'facture_lines')]
class FactureLine
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: Facture::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Facture $facture;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $variantId = null;

    #[ORM\Column(enumType: CommerceLineType::class)]
    private CommerceLineType $lineType;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantity;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $unitPrice;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $lineTotal;

    public function __construct(
        Facture $facture,
        ?Uuid $variantId,
        CommerceLineType $lineType,
        string $label,
        string $quantity,
        string $unitPrice,
        string $lineTotal,
    ) {
        $this->initializeUuid();
        $this->facture = $facture;
        $this->variantId = $variantId;
        $this->lineType = $lineType;
        $this->label = $label;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
    }

    public function getVariantId(): ?Uuid
    {
        return $this->variantId;
    }

    public function getLineType(): CommerceLineType
    {
        return $this->lineType;
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
