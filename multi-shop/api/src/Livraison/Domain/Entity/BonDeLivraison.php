<?php

namespace App\Livraison\Domain\Entity;

use App\Livraison\Domain\Enum\BonDeLivraisonStatus;
use App\Livraison\Infrastructure\Persistence\Doctrine\DoctrineBonDeLivraisonRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineBonDeLivraisonRepository::class)]
#[ORM\Table(name: 'bons_livraison')]
class BonDeLivraison implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid')]
    private Uuid $commandeId;

    #[ORM\Column(enumType: BonDeLivraisonStatus::class)]
    private BonDeLivraisonStatus $status = BonDeLivraisonStatus::Envoye;

    #[ORM\Column]
    private \DateTimeImmutable $sentAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    /** @var Collection<int, BonDeLivraisonLine> */
    #[ORM\OneToMany(targetEntity: BonDeLivraisonLine::class, mappedBy: 'bonDeLivraison', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Uuid $commandeId)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->reference = 'BL-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->commandeId = $commandeId;
        $this->sentAt = new \DateTimeImmutable();
        $this->lines = new ArrayCollection();
    }

    public function addLine(Uuid $variantId, string $label, string $quantity): BonDeLivraisonLine
    {
        $line = new BonDeLivraisonLine($this, $variantId, $label, $quantity);
        $this->lines->add($line);

        return $line;
    }

    public function markDelivered(): void
    {
        if (BonDeLivraisonStatus::Envoye !== $this->status) {
            throw new \DomainException('Only a sent delivery note can be marked as delivered.');
        }

        $this->status = BonDeLivraisonStatus::Delivre;
        $this->deliveredAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getCommandeId(): Uuid
    {
        return $this->commandeId;
    }

    public function getStatus(): BonDeLivraisonStatus
    {
        return $this->status;
    }

    public function getSentAt(): \DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function getDeliveredAt(): ?\DateTimeImmutable
    {
        return $this->deliveredAt;
    }

    /** @return Collection<int, BonDeLivraisonLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }
}
