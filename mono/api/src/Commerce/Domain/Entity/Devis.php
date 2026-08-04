<?php

namespace App\Commerce\Domain\Entity;

use App\Commerce\Domain\Enum\CommerceLineType;
use App\Commerce\Domain\Enum\DevisStatus;
use App\Commerce\Domain\ValueObject\Acheteur;
use App\Commerce\Infrastructure\Persistence\Doctrine\DoctrineDevisRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineDevisRepository::class)]
#[ORM\Table(name: 'devis')]
class Devis
{
    use UuidEntityTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $anonymousInfo = null;

    #[ORM\Column(enumType: DevisStatus::class)]
    private DevisStatus $status = DevisStatus::Actif;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $convertedVenteId = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $convertedCommandeId = null;

    /** @var Collection<int, DevisLine> */
    #[ORM\OneToMany(targetEntity: DevisLine::class, mappedBy: 'devis', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Acheteur $acheteur, ?\DateTimeImmutable $createdAt = null)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->reference = 'DEV-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->clientId = $acheteur->clientId();
        $this->anonymousInfo = $acheteur->anonymousInfo();
        if (null !== $createdAt) {
            $this->setCreatedAt($createdAt);
        }
        $this->lines = new ArrayCollection();
    }

    public function addLine(?Uuid $variantId, string $label, string $quantity, string $unitPrice, CommerceLineType $lineType): DevisLine
    {
        $this->assertMutable();
        $line = new DevisLine($this, $variantId, $lineType, $label, $quantity, $unitPrice);
        $this->lines->add($line);
        $this->totalAmount = bcadd($this->totalAmount, $line->getLineTotal(), 2);
        $this->touch();

        return $line;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): void
    {
        $this->validUntil = $validUntil;
        $this->touch();
    }

    public function refreshStatus(): void
    {
        if (DevisStatus::Actif !== $this->status || null === $this->validUntil) {
            return;
        }

        $today = new \DateTimeImmutable('today');
        if ($this->validUntil < $today) {
            $this->status = DevisStatus::Expire;
            $this->touch();
        }
    }

    public function cancel(): void
    {
        $this->assertConvertible();
        $this->status = DevisStatus::Annule;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function markConvertedToVente(Uuid $venteId): void
    {
        $this->assertConvertible();
        $this->status = DevisStatus::ConvertiVente;
        $this->convertedVenteId = $venteId;
        $this->touch();
    }

    public function markConvertedToCommande(Uuid $commandeId): void
    {
        $this->assertConvertible();
        $this->status = DevisStatus::ConvertiCommande;
        $this->convertedCommandeId = $commandeId;
        $this->touch();
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

    public function getStatus(): DevisStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getConvertedVenteId(): ?Uuid
    {
        return $this->convertedVenteId;
    }

    public function getConvertedCommandeId(): ?Uuid
    {
        return $this->convertedCommandeId;
    }

    /** @return Collection<int, DevisLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    private function assertMutable(): void
    {
        $this->refreshStatus();
        if (DevisStatus::Actif !== $this->status) {
            throw new \DomainException('Quote lines can only be added while the quote is active.');
        }
    }

    private function assertConvertible(): void
    {
        $this->refreshStatus();
        if (DevisStatus::Actif !== $this->status) {
            throw new \DomainException('Only an active quote can be modified or converted.');
        }
    }
}
