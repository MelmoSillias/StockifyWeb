<?php

namespace App\Facturation\Domain\Entity;

use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'avoir_lines')]
class AvoirLine
{
    use UuidEntityTrait;

    #[ORM\ManyToOne(targetEntity: Avoir::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Avoir $avoir;

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
        Avoir $avoir,
        Uuid $variantId,
        string $label,
        string $quantity,
        string $unitPrice,
        string $lineTotal,
    ) {
        $this->initializeUuid();
        $this->avoir = $avoir;
        $this->variantId = $variantId;
        $this->label = $label;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->lineTotal = $lineTotal;
    }
}
