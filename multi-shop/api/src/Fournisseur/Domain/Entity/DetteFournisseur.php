<?php

namespace App\Fournisseur\Domain\Entity;

use App\Fournisseur\Infrastructure\Persistence\Doctrine\DoctrineDetteFournisseurRepository;
use App\SharedKernel\Domain\Contract\ShopScopedInterface;
use App\SharedKernel\Domain\Trait\ShopScopedTrait;
use App\SharedKernel\Domain\Trait\UuidEntityTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineDetteFournisseurRepository::class)]
#[ORM\Table(name: 'dettes_fournisseur')]
class DetteFournisseur implements ShopScopedInterface
{
    use UuidEntityTrait;
    use ShopScopedTrait;

    #[ORM\Column(length: 30, unique: true)]
    private string $reference;

    #[ORM\Column(type: 'uuid')]
    private Uuid $fournisseurId;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $commandeFournisseurId = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private string $totalAmount;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null;

    #[ORM\Column]
    private \DateTimeImmutable $issuedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $creditClosedAt = null;

    public function __construct(Uuid $fournisseurId, string $totalAmount, ?string $label = null, ?Uuid $commandeFournisseurId = null)
    {
        if (bccomp($totalAmount, '0', 2) <= 0) {
            throw new \InvalidArgumentException('A supplier debt amount must be positive.');
        }

        $this->initializeUuid();
        $this->reference = 'DET-' . strtoupper(substr($this->getId()->toBase32(), -8));
        $this->fournisseurId = $fournisseurId;
        $this->totalAmount = $totalAmount;
        $this->label = $label;
        $this->commandeFournisseurId = $commandeFournisseurId;
        $this->issuedAt = new \DateTimeImmutable();
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getFournisseurId(): Uuid
    {
        return $this->fournisseurId;
    }

    public function getCommandeFournisseurId(): ?Uuid
    {
        return $this->commandeFournisseurId;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getCreditClosedAt(): ?\DateTimeImmutable
    {
        return $this->creditClosedAt;
    }

    public function closeCredit(\DateTimeImmutable $at): void
    {
        if (null !== $this->creditClosedAt) {
            return;
        }

        $this->creditClosedAt = $at;
    }

    public function reopenCredit(): void
    {
        $this->creditClosedAt = null;
    }
}
