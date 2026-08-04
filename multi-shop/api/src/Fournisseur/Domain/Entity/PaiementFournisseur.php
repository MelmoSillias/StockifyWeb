<?php

namespace App\Fournisseur\Domain\Entity;

use App\Fournisseur\Infrastructure\Persistence\Doctrine\DoctrinePaiementFournisseurRepository;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrinePaiementFournisseurRepository::class)]
#[ORM\Table(name: 'paiements_fournisseur')]
class PaiementFournisseur implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid')]
    private Uuid $detteFournisseurId;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'uuid')]
    private Uuid $modeDePaiementId;

    #[ORM\Column]
    private \DateTimeImmutable $paidAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    public function __construct(
        Uuid $detteFournisseurId,
        string $amount,
        Uuid $modeDePaiementId,
        ?\DateTimeImmutable $paidAt = null,
    ) {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A supplier payment amount must be positive.');
        }

        $this->initializeUuid();
        $this->reference = 'DEC-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->detteFournisseurId = $detteFournisseurId;
        $this->amount = $amount;
        $this->modeDePaiementId = $modeDePaiementId;
        $this->paidAt = $paidAt ?? new \DateTimeImmutable();
    }

    public function cancel(): void
    {
        if (null !== $this->cancelledAt) {
            throw new \DomainException('Supplier payment is already cancelled.');
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

    public function getDetteFournisseurId(): Uuid
    {
        return $this->detteFournisseurId;
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
