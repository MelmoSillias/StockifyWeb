<?php

namespace App\Paiement\Domain\Entity;

use App\Paiement\Infrastructure\Persistence\Doctrine\DoctrinePaiementRepository;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * An incoming monetary flow linked to an invoice or to an order (deposit).
 * It can be logically cancelled but is never deleted, and it never mutates
 * the invoice it settles.
 */
#[ORM\Entity(repositoryClass: DoctrinePaiementRepository::class)]
#[ORM\Table(name: 'paiements')]
class Paiement
{
    use UuidEntityTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $factureId = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $commandeId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'uuid')]
    private Uuid $modeDePaiementId;

    #[ORM\Column]
    private \DateTimeImmutable $paidAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct(
        string $amount,
        Uuid $modeDePaiementId,
        ?Uuid $factureId = null,
        ?Uuid $commandeId = null,
        ?\DateTimeImmutable $paidAt = null,
    ) {
        if (null === $factureId && null === $commandeId) {
            throw new \InvalidArgumentException('A payment must reference either an invoice or an order.');
        }
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A payment amount must be positive.');
        }

        $this->initializeUuid();
        $this->reference = 'PAY-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->amount = $amount;
        $this->modeDePaiementId = $modeDePaiementId;
        $this->factureId = $factureId;
        $this->commandeId = $commandeId;
        $this->paidAt = $paidAt ?? new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (null !== $this->cancelledAt) {
            throw new \DomainException('Payment is already cancelled.');
        }
        $this->cancelledAt = new \DateTimeImmutable();
    }

    public function isCancelled(): bool
    {
        return null !== $this->cancelledAt;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getFactureId(): ?Uuid
    {
        return $this->factureId;
    }

    public function getCommandeId(): ?Uuid
    {
        return $this->commandeId;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getModeDePaiementId(): Uuid
    {
        return $this->modeDePaiementId;
    }

    public function getPaidAt(): \DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }
}
