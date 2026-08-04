<?php

namespace App\Livraison\Domain\Entity;

use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'bon_livraison_lines')]
class BonDeLivraisonLine implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;

    #[ORM\ManyToOne(targetEntity: BonDeLivraison::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private BonDeLivraison $bonDeLivraison;

    #[ORM\Column(type: 'uuid')]
    private Uuid $variantId;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 3)]
    private string $quantity;

    public function __construct(
        BonDeLivraison $bonDeLivraison,
        Uuid $variantId,
        string $label,
        string $quantity,
    ) {
        $this->initializeUuid();
        $this->bonDeLivraison = $bonDeLivraison;
        $this->variantId = $variantId;
        $this->label = $label;
        $this->quantity = $quantity;
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
}
