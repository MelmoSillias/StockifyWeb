<?php

namespace App\Facturation\Domain\Entity;

use App\Commerce\Domain\Enum\CommerceLineType;
use App\Facturation\Infrastructure\Persistence\Doctrine\DoctrineAvoirRepository;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * A credit note issued to offset an immutable invoice (e.g. on sale cancellation).
 */
#[ORM\Entity(repositoryClass: DoctrineAvoirRepository::class)]
#[ORM\Table(name: 'avoirs')]
class Avoir implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $numero;

    #[ORM\Column(type: 'uuid')]
    private Uuid $factureId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $venteId;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column]
    private \DateTimeImmutable $issuedAt;

    /** @var Collection<int, AvoirLine> */
    #[ORM\OneToMany(targetEntity: AvoirLine::class, mappedBy: 'avoir', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Uuid $factureId, Uuid $venteId)
    {
        $this->initializeUuid();
        $this->numero = 'AVR-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->factureId = $factureId;
        $this->venteId = $venteId;
        $this->issuedAt = new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function addLine(?Uuid $variantId, CommerceLineType $lineType, string $label, string $quantity, string $unitPrice, string $lineTotal): void
    {
        $this->lines->add(new AvoirLine($this, $variantId, $lineType, $label, $quantity, $unitPrice, $lineTotal));
        $this->totalAmount = bcadd($this->totalAmount, $lineTotal, 2);
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function getFactureId(): Uuid
    {
        return $this->factureId;
    }

    public function getVenteId(): Uuid
    {
        return $this->venteId;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    /** @return Collection<int, AvoirLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }
}
