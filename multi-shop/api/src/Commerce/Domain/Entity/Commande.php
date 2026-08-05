<?php

namespace App\Commerce\Domain\Entity;

use App\Commerce\Domain\Enum\CommandeStatus;
use App\Commerce\Domain\Enum\CommerceLineType;
use App\Commerce\Domain\ValueObject\Acheteur;
use App\Commerce\Infrastructure\Persistence\Doctrine\DoctrineCommandeRepository;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\TimestampableTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineCommandeRepository::class)]
#[ORM\Table(name: 'commandes')]
class Commande implements ShopScopedInterface
{
    use UuidEntityTrait;
    use TimestampableTrait;
    use ShopScopedTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $clientId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $anonymousInfo = null;

    #[ORM\Column(enumType: CommandeStatus::class)]
    private CommandeStatus $status = CommandeStatus::Initiee;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $depositReceived = '0.00';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveryDate = null;

    /** @var Collection<int, CommandeLine> */
    #[ORM\OneToMany(targetEntity: CommandeLine::class, mappedBy: 'commande', cascade: ['persist'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct(Acheteur $acheteur)
    {
        $this->initializeUuid();
        $this->initializeTimestamps();
        $this->reference = 'CMD-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->clientId = $acheteur->clientId();
        $this->anonymousInfo = $acheteur->anonymousInfo();
        $this->lines = new ArrayCollection();
    }

    public function addLine(?Uuid $variantId, string $label, string $quantity, string $unitPrice, CommerceLineType $lineType): CommandeLine
    {
        $this->assertMutable();
        $line = new CommandeLine($this, $variantId, $lineType, $label, $quantity, $unitPrice);
        $this->lines->add($line);
        $this->totalAmount = bcadd($this->totalAmount, $line->getLineTotal(), 2);

        return $line;
    }

    public function confirm(\DateTimeImmutable $deliveryDate): void
    {
        if (CommandeStatus::Initiee !== $this->status) {
            throw new \DomainException('Only an initiated order can be confirmed.');
        }
        $this->status = CommandeStatus::Confirmee;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->deliveryDate = $deliveryDate;
        $this->touch();
    }

    public function cancel(): void
    {
        if (\in_array($this->status, [CommandeStatus::Livree, CommandeStatus::Annulee], true)) {
            throw new \DomainException('This order can no longer be cancelled.');
        }
        $this->status = CommandeStatus::Annulee;
        $this->cancelledAt = new \DateTimeImmutable();
        $this->touch();
    }

    public function registerDeposit(string $amount): void
    {
        $this->depositReceived = bcadd($this->depositReceived, $amount, 2);
        $this->touch();
    }

    /**
     * @param array<string, string> $shippedQuantitiesByVariant variantId => quantity
     */
    public function syncDeliveryStatus(array $shippedQuantitiesByVariant): void
    {
        if (!\in_array($this->status, [CommandeStatus::Confirmee, CommandeStatus::PartiellementLivree, CommandeStatus::Livree], true)) {
            return;
        }

        $anyShipped = false;
        $allShipped = true;

        foreach ($this->lines as $line) {
            if ($line->isLibre()) {
                continue;
            }

            $shipped = $shippedQuantitiesByVariant[(string) $line->getVariantId()] ?? '0';

            if (bccomp($shipped, '0', 3) > 0) {
                $anyShipped = true;
            }

            if (bccomp($shipped, $line->getQuantity(), 3) < 0) {
                $allShipped = false;
            }
        }

        if ($allShipped && $anyShipped) {
            $this->status = CommandeStatus::Livree;
        } elseif ($anyShipped) {
            $this->status = CommandeStatus::PartiellementLivree;
        }

        $this->touch();
    }

    public function wasConfirmed(): bool
    {
        return null !== $this->confirmedAt;
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

    public function getStatus(): CommandeStatus
    {
        return $this->status;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getDepositReceived(): string
    {
        return $this->depositReceived;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    /** @return Collection<int, CommandeLine> */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    private function assertMutable(): void
    {
        if (CommandeStatus::Initiee !== $this->status) {
            throw new \DomainException('Lines can only be added while the order is initiated.');
        }
    }
}
