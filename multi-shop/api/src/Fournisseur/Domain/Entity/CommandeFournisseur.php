<?php

namespace App\Fournisseur\Domain\Entity;

use App\Fournisseur\Domain\Enum\CommandeFournisseurStatus;
use App\Fournisseur\Infrastructure\Persistence\Doctrine\DoctrineCommandeFournisseurRepository;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineCommandeFournisseurRepository::class)]
#[ORM\Table(name: 'commandes_fournisseur')]
class CommandeFournisseur implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;
    use TimestampableTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid')]
    private Uuid $fournisseurId;

    #[ORM\Column(enumType: CommandeFournisseurStatus::class)]
    private CommandeFournisseurStatus $status = CommandeFournisseurStatus::Initiee;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $depositPaid = '0.00';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expectedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $receivedAt = null;

    /** @var Collection<int, CommandeFournisseurLine> */
    #[ORM\OneToMany(targetEntity: CommandeFournisseurLine::class, mappedBy: 'commande', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Uuid $fournisseurId)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->reference = 'ACH-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->fournisseurId = $fournisseurId;
        $this->lines = new ArrayCollection();
    }

    public function addLine(Uuid $variantId, string $label, string $quantity, string $unitCost): CommandeFournisseurLine
    {
        $this->assertMutable();
        $line = new CommandeFournisseurLine($this, $variantId, $label, $quantity, $unitCost);
        $this->lines->add($line);
        $this->totalAmount = bcadd($this->totalAmount, $line->getLineTotal(), 2);

        return $line;
    }

    public function confirm(?\DateTimeImmutable $expectedAt = null): void
    {
        if (CommandeFournisseurStatus::Initiee !== $this->status) {
            throw new \DomainException('Only an initiated purchase order can be confirmed.');
        }
        if ($this->lines->isEmpty()) {
            throw new \DomainException('Cannot confirm a purchase order without lines.');
        }

        $this->status = CommandeFournisseurStatus::Confirmee;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->expectedAt = $expectedAt;
        $this->touch();
    }

    public function receive(): void
    {
        if (CommandeFournisseurStatus::Confirmee !== $this->status) {
            throw new \DomainException('Only a confirmed purchase order can be received.');
        }

        $this->status = CommandeFournisseurStatus::Recue;
        $this->receivedAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function cancel(): void
    {
        if (\in_array($this->status, [CommandeFournisseurStatus::Recue, CommandeFournisseurStatus::Annulee], true)) {
            throw new \DomainException('This purchase order can no longer be cancelled.');
        }

        $this->status = CommandeFournisseurStatus::Annulee;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getFournisseurId(): Uuid
    {
        return $this->fournisseurId;
    }

    public function getStatus(): CommandeFournisseurStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getDepositPaid(): string
    {
        return $this->depositPaid;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getExpectedAt(): ?\DateTimeImmutable
    {
        return $this->expectedAt;
    }

    public function getReceivedAt(): ?\DateTimeImmutable
    {
        return $this->receivedAt;
    }

    /** @return Collection<int, CommandeFournisseurLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    private function assertMutable(): void
    {
        if (CommandeFournisseurStatus::Initiee !== $this->status) {
            throw new \DomainException('Lines can only be added while the purchase order is initiated.');
        }
    }
}
