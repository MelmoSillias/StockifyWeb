<?php

namespace App\Commerce\Domain\Entity;

use App\Commerce\Domain\Enum\CommerceLineType;
use App\Commerce\Domain\ValueObject\Acheteur;
use App\Commerce\Infrastructure\Persistence\Doctrine\DoctrineVenteRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineVenteRepository::class)]
#[ORM\Table(name: 'ventes')]
class Vente
{
    use UuidEntityTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $anonymousInfo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    /** @var Collection<int, VenteLine> */
    #[ORM\OneToMany(targetEntity: VenteLine::class, mappedBy: 'vente', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Acheteur $acheteur, ?\DateTimeImmutable $createdAt = null)
    {
        $this->initializeUuid();
        $this->reference = 'VTE-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->clientId = $acheteur->clientId();
        $this->anonymousInfo = $acheteur->anonymousInfo();
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function addLine(?Uuid $variantId, string $label, string $quantity, string $unitPrice, CommerceLineType $lineType): VenteLine
    {
        $line = new VenteLine($this, $variantId, $lineType, $label, $quantity, $unitPrice);
        $this->lines->add($line);
        $this->totalAmount = bcadd($this->totalAmount, $line->getLineTotal(), 2);

        return $line;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getAcheteur(): Acheteur
    {
        return $this->clientId
            ? Acheteur::fromClient($this->clientId)
            : Acheteur::anonymous((string) $this->anonymousInfo);
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function cancel(): void
    {
        if (null !== $this->cancelledAt) {
            throw new \DomainException('Sale is already cancelled.');
        }
        $this->cancelledAt = new \DateTimeImmutable();
    }

    public function isCancelled(): bool
    {
        return null !== $this->cancelledAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    /** @return Collection<int, VenteLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }
}
